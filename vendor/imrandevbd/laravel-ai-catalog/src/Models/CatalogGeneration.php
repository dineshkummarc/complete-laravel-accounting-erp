<?php

namespace ImranDevBd\AiCatalog\Models;

use ImranDevBd\AiCatalog\Data\GeneratedListing;
use ImranDevBd\AiCatalog\Enums\GenerationStatus;
use ImranDevBd\AiCatalog\Enums\InputMode;
use ImranDevBd\AiCatalog\Enums\Tone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class CatalogGeneration extends Model
{
    protected $table = 'ai_catalog_generations';

    protected $guarded = [];

    protected $casts = [
        'input_mode' => InputMode::class,
        'tone' => Tone::class,
        'status' => GenerationStatus::class,
        'input_payload' => 'array',
        'payload' => 'array',
        'mapped_payload' => 'array',
        'confidence' => 'array',
        'variants' => 'array',
        'flags' => 'array',
        'overall_confidence' => 'float',
        'cost_usd' => 'float',
        'latency_ms' => 'float',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (! $model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });

        static::addGlobalScope('tenant', function (Builder $query): void {
            if (! config('ai-catalog.tenancy.enabled')) {
                return;
            }
            $resolver = config('ai-catalog.tenancy.resolver');
            $tenantId = is_callable($resolver) ? $resolver() : null;
            if ($tenantId) {
                $query->where($query->getModel()->getTable().'.tenant_id', $tenantId);
            }
        });
    }

    public function product(): MorphTo
    {
        return $this->morphTo();
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(CatalogRevision::class, 'generation_id');
    }

    public function listing(): GeneratedListing
    {
        return GeneratedListing::fromArray($this->payload ?? []);
    }

    public function needsReview(): bool
    {
        $threshold = (float) config('ai-catalog.workflow.low_confidence_threshold', 0.7);

        return $this->overall_confidence !== null && $this->overall_confidence < $threshold;
    }

    /**
     * @return array<string, float>
     */
    public function lowConfidenceFields(): array
    {
        $threshold = (float) config('ai-catalog.workflow.low_confidence_threshold', 0.7);
        $fields = [];
        foreach ((array) $this->confidence as $field => $score) {
            if ((float) $score < $threshold) {
                $fields[(string) $field] = (float) $score;
            }
        }

        return $fields;
    }
}
