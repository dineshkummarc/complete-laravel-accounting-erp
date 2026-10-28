<?php

namespace ImranDevBd\AiCatalog;

use ImranDevBd\AiCatalog\Data\GenerationInput;
use ImranDevBd\AiCatalog\Enums\InputMode;
use ImranDevBd\AiCatalog\Enums\Tone;
use ImranDevBd\AiCatalog\Jobs\GenerateListingJob;
use ImranDevBd\AiCatalog\Models\CatalogGeneration;
use ImranDevBd\AiCatalog\Services\ListingGenerator;
use Illuminate\Database\Eloquent\Model;

class PendingGeneration
{
    protected GenerationInput $input;

    public function __construct(
        protected ListingGenerator $generator,
        ?GenerationInput $input = null,
    ) {
        $this->input = $input ?? new GenerationInput(
            mode: InputMode::Keyword,
            locale: (string) config('ai-catalog.locale', 'en'),
            tone: Tone::tryFrom((string) config('ai-catalog.tone', 'professional')) ?? Tone::Professional,
            variants: (int) config('ai-catalog.variants', 2),
        );
    }

    public function fromKeyword(string $keyword): self
    {
        $this->input->mode = InputMode::Keyword;
        $this->input->keyword = $keyword;

        return $this;
    }

    public function fromText(string $text): self
    {
        $this->input->mode = InputMode::Text;
        $this->input->text = $text;

        return $this;
    }

    public function fromImage(string $pathOrUrl): self
    {
        $this->input->mode = InputMode::Image;
        $this->input->image = $pathOrUrl;

        return $this;
    }

    public function fromUrl(string $url): self
    {
        $this->input->mode = InputMode::Url;
        $this->input->url = $url;

        return $this;
    }

    public function fromVoice(string $audioPath): self
    {
        $this->input->mode = InputMode::Voice;
        $this->input->audio = $audioPath;

        return $this;
    }

    public function fromDocument(string $path): self
    {
        $this->input->mode = InputMode::Document;
        $this->input->document = $path;

        return $this;
    }

    public function locale(string $locale): self
    {
        $this->input->locale = $locale;

        return $this;
    }

    /**
     * @param  list<string>  $locales
     */
    public function locales(array $locales): self
    {
        $this->input->locales = array_values($locales);

        return $this;
    }

    public function tone(string|Tone $tone): self
    {
        $this->input->tone = $tone instanceof Tone ? $tone : (Tone::tryFrom($tone) ?? Tone::Professional);

        return $this;
    }

    public function brandVoice(?string $name): self
    {
        $this->input->brandVoice = $name;

        return $this;
    }

    public function variants(int $count): self
    {
        $this->input->variants = max(0, min($count, (int) config('ai-catalog.max_variants', 3)));

        return $this;
    }

    /**
     * @param  list<string>  $fields
     */
    public function fields(array $fields): self
    {
        $this->input->onlyFields = $fields;

        return $this;
    }

    /**
     * @param  array<int, mixed>  $categories
     */
    public function taxonomy(array $categories): self
    {
        $this->input->taxonomy = $categories;

        return $this;
    }

    public function forProduct(Model|int|string|null $product, ?string $type = null): self
    {
        $this->input->product = $product;
        $this->input->productType = $type;

        return $this;
    }

    public function tenant(?string $id): self
    {
        $this->input->tenantId = $id;

        return $this;
    }

    public function campaign(?string $name): self
    {
        $this->input->campaign = $name;

        return $this;
    }

    public function region(?string $region): self
    {
        $this->input->region = $region;

        return $this;
    }

    public function withDpp(bool $enabled = true): self
    {
        $this->input->meta['dpp'] = $enabled;

        return $this;
    }

    public function withSocial(bool $enabled = true): self
    {
        $this->input->meta['social'] = $enabled;

        return $this;
    }

    public function withSpatial(bool $enabled = true): self
    {
        $this->input->meta['spatial'] = $enabled;

        return $this;
    }

    public function withReturnReduction(bool $enabled = true): self
    {
        $this->input->meta['return_reduction'] = $enabled;

        return $this;
    }

    public function withEmbeddings(bool $enabled = true): self
    {
        $this->input->meta['embeddings'] = $enabled;

        return $this;
    }

    public function persona(string $persona): self
    {
        $this->input->meta['persona'] = $persona;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function meta(array $meta): self
    {
        $this->input->meta = array_merge($this->input->meta, $meta);

        return $this;
    }

    public function input(): GenerationInput
    {
        return $this->input;
    }

    public function generate(): CatalogGeneration
    {
        return $this->generator->generate($this->input);
    }

    public function generateAsync(): CatalogGeneration
    {
        $hydrated = $this->generator->hydrate($this->input);
        $record = CatalogGeneration::query()->create([
            'tenant_id' => $hydrated->tenantId,
            'product_type' => $hydrated->product instanceof Model ? $hydrated->product::class : $hydrated->productType,
            'product_id' => $hydrated->product instanceof Model ? $hydrated->product->getKey() : (is_scalar($hydrated->product) ? $hydrated->product : null),
            'input_mode' => $hydrated->mode,
            'input_payload' => $hydrated->cachePayload(),
            'locale' => $hydrated->locale,
            'tone' => $hydrated->tone,
            'brand_voice' => $hydrated->brandVoice,
            'status' => \ImranDevBd\AiCatalog\Enums\GenerationStatus::Pending,
            'cache_key' => \ImranDevBd\AiCatalog\Support\InputHasher::make($hydrated->cachePayload()),
        ]);

        GenerateListingJob::dispatch($record->id)->onQueue((string) config('ai-catalog.queue', 'default'));

        return $record;
    }
}
