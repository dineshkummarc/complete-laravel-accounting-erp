<?php

namespace ImranDevBd\AiCatalog\Data;

use ImranDevBd\AiCatalog\Enums\InputMode;
use ImranDevBd\AiCatalog\Enums\Tone;
use Illuminate\Database\Eloquent\Model;

class GenerationInput
{
    /**
     * @param  array<int, array{id: int|string, name: string, parent_id?: int|string|null}>  $taxonomy
     * @param  array<int, string>  $locales
     * @param  list<string>|null  $onlyFields
     */
    public function __construct(
        public InputMode $mode,
        public ?string $keyword = null,
        public ?string $text = null,
        public ?string $image = null,
        public ?string $url = null,
        public ?string $audio = null,
        public ?string $document = null,
        public string $locale = 'en',
        public array $locales = [],
        public Tone $tone = Tone::Professional,
        public ?string $brandVoice = null,
        public int $variants = 2,
        public array $taxonomy = [],
        public ?array $onlyFields = null,
        public Model|int|string|null $product = null,
        public ?string $productType = null,
        public ?string $tenantId = null,
        public ?string $campaign = null,
        public ?string $region = null,
        public array $meta = [],
    ) {}

    public function promptSeed(): string
    {
        return match ($this->mode) {
            InputMode::Image => 'Analyze the product photo and produce a complete listing.',
            InputMode::Url => 'Rewrite unique catalog copy from the source page. Do not copy sentences.',
            InputMode::Voice => (string) ($this->text ?: 'Structure a listing from the dictated product notes.'),
            InputMode::Document => (string) ($this->text ?: 'Extract a listing from the attached datasheet.'),
            InputMode::Text => (string) $this->text,
            default => (string) ($this->keyword ?: $this->text),
        };
    }

    public function extraLocales(): array
    {
        $primary = strtolower($this->locale);
        $list = $this->locales !== [] ? $this->locales : [];

        return array_values(array_unique(array_filter(
            array_map('strtolower', $list),
            fn (string $locale) => $locale !== '' && $locale !== $primary
        )));
    }

    public function cachePayload(): array
    {
        return [
            'mode' => $this->mode->value,
            'keyword' => $this->keyword,
            'text' => $this->text,
            'image' => $this->image,
            'url' => $this->url,
            'audio' => $this->audio,
            'document' => $this->document,
            'locale' => $this->locale,
            'locales' => $this->locales,
            'tone' => $this->tone->value,
            'brand_voice' => $this->brandVoice,
            'variants' => $this->variants,
            'only_fields' => $this->onlyFields,
            'taxonomy' => $this->taxonomy,
            'campaign' => $this->campaign,
            'region' => $this->region,
            'meta' => $this->meta,
        ];
    }
}
