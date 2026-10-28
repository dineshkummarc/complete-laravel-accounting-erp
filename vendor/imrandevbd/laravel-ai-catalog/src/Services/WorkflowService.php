<?php

namespace ImranDevBd\AiCatalog\Services;

use ImranDevBd\AiCatalog\Enums\GenerationStatus;
use ImranDevBd\AiCatalog\Events\ProductAiApproved;
use ImranDevBd\AiCatalog\Events\ProductAiReviewed;
use ImranDevBd\AiCatalog\Exceptions\WorkflowException;
use ImranDevBd\AiCatalog\Models\CatalogGeneration;
use ImranDevBd\AiCatalog\Models\CatalogRevision;
use ImranDevBd\AiCatalog\Support\WebhookDispatcher;
use Illuminate\Database\Eloquent\Model;

class WorkflowService
{
    public function __construct(
        protected SchemaMapper $mapper = new SchemaMapper,
        protected WebhookDispatcher $webhooks = new WebhookDispatcher,
    ) {}

    public function submitForReview(CatalogGeneration $generation, ?int $actorId = null): CatalogGeneration
    {
        $this->assertStatus($generation, [GenerationStatus::Draft, GenerationStatus::Rejected]);

        return $this->transition($generation, GenerationStatus::InReview, 'reviewed', $actorId, [
            'reviewed_by' => $actorId,
            'reviewed_at' => now(),
        ]);
    }

    public function reject(CatalogGeneration $generation, ?string $reason = null, ?int $actorId = null): CatalogGeneration
    {
        $this->assertStatus($generation, [GenerationStatus::Draft, GenerationStatus::InReview]);

        $generation = $this->transition($generation, GenerationStatus::Rejected, 'rejected', $actorId, [], [
            'reason' => $reason,
        ]);

        event(new ProductAiReviewed($generation, 'rejected'));
        $this->webhooks->send('ProductAiReviewed', $generation, ['decision' => 'rejected']);

        return $generation;
    }

    public function approve(CatalogGeneration $generation, ?int $actorId = null, bool $writeProduct = true): CatalogGeneration
    {
        $this->assertStatus($generation, [GenerationStatus::Draft, GenerationStatus::InReview]);

        if (config('ai-catalog.workflow.require_review') && $generation->status === GenerationStatus::Draft && $generation->needsReview()) {
            throw new WorkflowException('Low-confidence drafts must be reviewed before approval.');
        }

        $product = null;
        if ($writeProduct) {
            $input = app(ListingGenerator::class)->inputFromGeneration($generation);
            $product = $this->mapper->apply($generation->listing(), $input, $this->mapper->resolveProduct($input));
            $generation->product()->associate($product);
            $generation->mapped_payload = $this->mapper->attributes($generation->listing(), $input);
        }

        $generation = $this->transition($generation, GenerationStatus::Approved, 'approved', $actorId, [
            'approved_by' => $actorId,
            'approved_at' => now(),
        ]);

        if (config('ai-catalog.workflow.auto_publish')) {
            $generation = $this->publish($generation, $actorId);
        }

        event(new ProductAiApproved($generation, $product));
        event(new ProductAiReviewed($generation, 'approved'));
        $this->webhooks->send('ProductAiApproved', $generation);

        return $generation;
    }

    public function publish(CatalogGeneration $generation, ?int $actorId = null): CatalogGeneration
    {
        $this->assertStatus($generation, [GenerationStatus::Approved]);

        return $this->transition($generation, GenerationStatus::Published, 'published', $actorId, [
            'published_at' => now(),
        ]);
    }

    /**
     * Human edit of generated payload without regenerating.
     *
     * @param  array<string, mixed>  $payload
     */
    public function edit(CatalogGeneration $generation, array $payload, ?int $actorId = null): CatalogGeneration
    {
        if (! $generation->status?->canEdit()) {
            throw new WorkflowException("Generation [{$generation->status?->value}] cannot be edited.");
        }

        $before = $generation->payload;
        $merged = array_merge($generation->payload ?? [], $payload);
        $generation->payload = $merged;
        $generation->save();

        $this->revision($generation, 'edited', $before, $merged, $actorId, 'human');

        return $generation->refresh();
    }

    /**
     * @param  array<int, GenerationStatus>  $allowed
     */
    protected function assertStatus(CatalogGeneration $generation, array $allowed): void
    {
        if (! in_array($generation->status, $allowed, true)) {
            $names = implode(', ', array_map(fn (GenerationStatus $s) => $s->value, $allowed));
            throw new WorkflowException("Expected status [{$names}], got [{$generation->status?->value}].");
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $meta
     */
    protected function transition(
        CatalogGeneration $generation,
        GenerationStatus $status,
        string $action,
        ?int $actorId,
        array $attributes = [],
        array $meta = [],
    ): CatalogGeneration {
        $before = ['status' => $generation->status?->value];
        $generation->fill($attributes);
        $generation->status = $status;
        $generation->save();

        $this->revision($generation, $action, $before, ['status' => $status->value], $actorId, 'human', $meta);

        return $generation->refresh();
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>  $meta
     */
    public function revision(
        CatalogGeneration $generation,
        string $action,
        ?array $before,
        ?array $after,
        ?int $actorId = null,
        string $actorType = 'ai',
        array $meta = [],
    ): CatalogRevision {
        return $generation->revisions()->create([
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'action' => $action,
            'before' => $before,
            'after' => $after,
            'meta' => $meta ?: null,
            'created_at' => now(),
        ]);
    }
}
