<?php

namespace ImranDevBd\AiCatalog\Services;

use ImranDevBd\AiCatalog\Data\GeneratedListing;
use ImranDevBd\AiCatalog\Data\GenerationInput;
use ImranDevBd\AiCatalog\Enums\GenerationStatus;
use ImranDevBd\AiCatalog\Enums\InputMode;
use ImranDevBd\AiCatalog\Enums\Tone;
use ImranDevBd\AiCatalog\Events\ProductAiFailed;
use ImranDevBd\AiCatalog\Events\ProductAiGenerated;
use ImranDevBd\AiCatalog\Exceptions\AiCatalogException;
use ImranDevBd\AiCatalog\Models\CatalogGeneration;
use ImranDevBd\AiCatalog\Support\InputHasher;
use ImranDevBd\AiCatalog\Support\ListingSchema;
use ImranDevBd\AiCatalog\Support\WebhookDispatcher;
use ImranDevBd\AiHub\Data\AiResponse;
use Illuminate\Database\Eloquent\Model;

class ListingGenerator
{
    public function __construct(
        protected HubClient $hub = new HubClient,
        protected PromptBuilder $prompts = new PromptBuilder,
        protected TaxonomyMatcher $taxonomy = new TaxonomyMatcher,
        protected ConfidenceScorer $confidence = new ConfidenceScorer,
        protected DuplicateChecker $duplicates = new DuplicateChecker,
        protected ComplianceChecker $compliance = new ComplianceChecker,
        protected SchemaMapper $mapper = new SchemaMapper,
        protected UrlReader $urls = new UrlReader,
        protected WorkflowService $workflow = new WorkflowService,
        protected WebhookDispatcher $webhooks = new WebhookDispatcher,
        protected ListingEnricher $enricher = new ListingEnricher,
        protected VoiceTranscriber $voice = new VoiceTranscriber,
        protected DocumentReader $documents = new DocumentReader,
        protected \ImranDevBd\AiCatalog\Support\CostTracker $budget = new \ImranDevBd\AiCatalog\Support\CostTracker,
    ) {}

    public function generate(GenerationInput $input, ?CatalogGeneration $record = null): CatalogGeneration
    {
        $input = $this->hydrate($input);
        $record ??= $this->startRecord($input);

        try {
            $record->status = GenerationStatus::Generating;
            $record->save();

            $this->budget->assertWithinBudget();

            if ($input->mode === InputMode::Voice && $input->audio) {
                $input->text = $this->voice->transcribe($input->audio);
            }

            if ($input->mode === InputMode::Document && $input->document) {
                $input->text = $this->documents->read($input->document);
            }

            if ($input->mode === InputMode::Url && $input->url) {
                $page = $this->urls->fetch($input->url);
                $input->text = trim(($input->text ?? '')."\n".$page);
            }

            $response = $this->hub->complete(
                ListingSchema::listing(),
                $this->prompts->system($input),
                $this->prompts->listing($input),
                $input,
            );

            $listing = $this->listingFromResponse($response, $input);
            $this->persistSuccess($record, $listing, $response, $input);

            event(new ProductAiGenerated($record));
            $this->webhooks->send('ProductAiGenerated', $record);

            return $record->refresh();
        } catch (\Throwable $e) {
            $record->status = GenerationStatus::Failed;
            $record->error = $e->getMessage();
            $record->save();

            $this->workflow->revision($record, 'failed', null, ['error' => $e->getMessage()]);

            event(new ProductAiFailed($record, $e->getMessage()));
            $this->webhooks->send('ProductAiFailed', $record, ['message' => $e->getMessage()]);

            throw new AiCatalogException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function regenerateField(CatalogGeneration $generation, string $field, ?int $actorId = null): CatalogGeneration
    {
        $input = $this->inputFromGeneration($generation);
        $listing = $generation->listing();

        $response = $this->hub->complete(
            ListingSchema::field($field),
            $this->prompts->system($input),
            $this->prompts->field($field, $listing, $input),
            $input,
            'ai-catalog.regenerate-field',
        );

        $decoded = $response->json() ?? [];
        $value = $decoded['value'] ?? null;
        $score = isset($decoded['confidence']) ? (float) $decoded['confidence'] : 0.8;

        $payload = $listing->toArray();
        $map = [
            'title' => 'title',
            'meta_title' => 'meta_title',
            'meta_description' => 'meta_description',
            'short_description' => 'short_description',
            'long_description' => 'long_description',
            'specs' => 'specs',
            'tags' => 'tags',
            'faq' => 'faq',
            'care_instructions' => 'care_instructions',
            'alt_text' => 'alt_text',
        ];

        if (! isset($map[$field])) {
            throw new AiCatalogException("Unknown field [{$field}].");
        }

        $before = $payload[$map[$field]] ?? null;
        $payload[$map[$field]] = $value;
        $payload['confidence'][$field] = $score;

        $updated = $this->finalizeListing(GeneratedListing::fromArray($payload), $input);

        $generation->payload = $updated->toArray();
        $generation->confidence = $updated->confidence;
        $generation->overall_confidence = $updated->overallConfidence();
        $generation->flags = $updated->flags;
        $generation->variants = $updated->variants;
        $generation->mapped_payload = $this->mapper->attributes($updated, $input);
        $generation->prompt_tokens += $response->promptTokens;
        $generation->completion_tokens += $response->completionTokens;
        $generation->cost_usd += $response->costUsd;
        $generation->save();

        $this->workflow->revision($generation, 'regenerated', [$field => $before], [$field => $value], $actorId, 'ai', [
            'field' => $field,
        ]);

        return $generation->refresh();
    }

    public function inputFromGeneration(CatalogGeneration $generation): GenerationInput
    {
        $payload = $generation->input_payload ?? [];
        $product = $generation->product_id ? $generation->product : null;

        return $this->hydrate(new GenerationInput(
            mode: $generation->input_mode instanceof InputMode ? $generation->input_mode : InputMode::from((string) $generation->input_mode),
            keyword: $payload['keyword'] ?? null,
            text: $payload['text'] ?? null,
            image: $payload['image'] ?? null,
            url: $payload['url'] ?? null,
            audio: $payload['audio'] ?? null,
            document: $payload['document'] ?? null,
            locale: $generation->locale ?: 'en',
            locales: $payload['locales'] ?? [],
            tone: $generation->tone instanceof Tone ? $generation->tone : Tone::tryFrom((string) $generation->tone) ?? Tone::Professional,
            brandVoice: $generation->brand_voice,
            variants: (int) ($payload['variants'] ?? config('ai-catalog.variants', 2)),
            taxonomy: $payload['taxonomy'] ?? [],
            onlyFields: $payload['only_fields'] ?? null,
            product: $product,
            productType: $generation->product_type,
            tenantId: $generation->tenant_id,
            campaign: $payload['campaign'] ?? null,
            region: $payload['region'] ?? null,
            meta: $payload['meta'] ?? [],
        ));
    }

    public function hydrate(GenerationInput $input): GenerationInput
    {
        if ($input->taxonomy === []) {
            $input->taxonomy = $this->taxonomy->load();
        } else {
            $input->taxonomy = $this->taxonomy->normalize($input->taxonomy);
        }

        if ($input->tenantId === null && config('ai-catalog.tenancy.enabled')) {
            $resolver = config('ai-catalog.tenancy.resolver');
            $input->tenantId = is_callable($resolver) ? $resolver() : null;
        }

        $product = $this->mapper->resolveProduct($input);
        if ($product instanceof Model) {
            $input->product = $product;
            $input->productType = $product::class;
        }

        return $input;
    }

    protected function listingFromResponse(AiResponse $response, GenerationInput $input): GeneratedListing
    {
        $decoded = $response->json();
        if (! is_array($decoded)) {
            throw new AiCatalogException('AI response was not valid listing JSON.');
        }

        return $this->finalizeListing(GeneratedListing::fromArray($decoded), $input);
    }

    public function finalizeListing(GeneratedListing $listing, GenerationInput $input): GeneratedListing
    {
        $category = $this->taxonomy->match($listing->suggestedCategory, $input->taxonomy);
        if ($category) {
            $listing->categoryId = $category['id'];
            $listing->suggestedCategory = $category['name'];
            $listing->confidence['category'] = min(1, ($category['score'] / 100));
        } else {
            $listing->categoryId = null;
            if ($listing->suggestedCategory) {
                $listing->flags[] = 'unmatched_category';
                $listing->suggestedCategory = null;
            }
        }

        $sub = $this->taxonomy->match($listing->suggestedSubcategory, $input->taxonomy);
        if ($sub) {
            $listing->subcategoryId = $sub['id'];
            $listing->suggestedSubcategory = $sub['name'];
        } else {
            $listing->subcategoryId = null;
            $listing->suggestedSubcategory = null;
        }

        $listing = $this->confidence->enrich($listing);
        $listing = $this->enricher->enrich($listing, $input);
        $listing->flags = array_values(array_unique(array_merge(
            $listing->flags,
            $this->duplicates->flags($listing),
            $this->compliance->flags($listing, $input->region),
        )));

        return $listing;
    }

    protected function startRecord(GenerationInput $input): CatalogGeneration
    {
        $product = $input->product instanceof Model ? $input->product : null;

        return CatalogGeneration::query()->create([
            'tenant_id' => $input->tenantId,
            'product_type' => $product ? $product::class : $input->productType,
            'product_id' => $product?->getKey() ?? (is_scalar($input->product) ? $input->product : null),
            'input_mode' => $input->mode,
            'input_payload' => $input->cachePayload(),
            'locale' => $input->locale,
            'tone' => $input->tone,
            'brand_voice' => $input->brandVoice,
            'status' => GenerationStatus::Pending,
            'cache_key' => InputHasher::make($input->cachePayload()),
        ]);
    }

    protected function persistSuccess(
        CatalogGeneration $record,
        GeneratedListing $listing,
        AiResponse $response,
        GenerationInput $input,
    ): void {
        $requireReview = (bool) config('ai-catalog.workflow.require_review', true);
        $threshold = (float) config('ai-catalog.workflow.low_confidence_threshold', 0.7);
        $status = ($requireReview && $listing->overallConfidence() < $threshold)
            ? GenerationStatus::Draft
            : GenerationStatus::Draft;

        $record->fill([
            'status' => $status,
            'payload' => $listing->toArray(),
            'mapped_payload' => $this->mapper->attributes($listing, $input),
            'confidence' => $listing->confidence,
            'overall_confidence' => $listing->overallConfidence(),
            'variants' => $listing->variants,
            'flags' => $listing->flags,
            'provider' => $response->provider,
            'model' => $response->model,
            'prompt_tokens' => $response->promptTokens,
            'completion_tokens' => $response->completionTokens,
            'cost_usd' => $response->costUsd,
            'latency_ms' => $response->latencyMs,
            'error' => null,
        ]);
        $record->save();

        $this->workflow->revision($record, 'generated', null, $listing->toArray());
    }
}
