<?php

namespace ImranDevBd\AiCatalog;

use ImranDevBd\AiCatalog\Models\CatalogGeneration;
use ImranDevBd\AiCatalog\Services\ListingGenerator;
use ImranDevBd\AiCatalog\Services\WorkflowService;
use Illuminate\Database\Eloquent\Model;

class CatalogManager
{
    public function __construct(
        protected ListingGenerator $generator,
        protected WorkflowService $workflow,
    ) {}

    public function pending(): PendingGeneration
    {
        return new PendingGeneration($this->generator);
    }

    public function fromKeyword(string $keyword): PendingGeneration
    {
        return $this->pending()->fromKeyword($keyword);
    }

    public function fromText(string $text): PendingGeneration
    {
        return $this->pending()->fromText($text);
    }

    public function fromImage(string $pathOrUrl): PendingGeneration
    {
        return $this->pending()->fromImage($pathOrUrl);
    }

    public function fromUrl(string $url): PendingGeneration
    {
        return $this->pending()->fromUrl($url);
    }

    public function fromVoice(string $audioPath): PendingGeneration
    {
        return $this->pending()->fromVoice($audioPath);
    }

    public function fromDocument(string $path): PendingGeneration
    {
        return $this->pending()->fromDocument($path);
    }

    public function forProduct(Model|int|string $product, ?string $type = null): PendingGeneration
    {
        return $this->pending()->forProduct($product, $type);
    }

    public function generate(PendingGeneration|string $input): CatalogGeneration
    {
        if (is_string($input)) {
            return $this->fromKeyword($input)->generate();
        }

        return $input->generate();
    }

    public function regenerateField(CatalogGeneration|int $generation, string $field, ?int $actorId = null): CatalogGeneration
    {
        $record = $generation instanceof CatalogGeneration
            ? $generation
            : CatalogGeneration::query()->findOrFail($generation);

        return $this->generator->regenerateField($record, $field, $actorId);
    }

    public function approve(CatalogGeneration|int $generation, ?int $actorId = null): CatalogGeneration
    {
        $record = $generation instanceof CatalogGeneration
            ? $generation
            : CatalogGeneration::query()->findOrFail($generation);

        return $this->workflow->approve($record, $actorId);
    }

    public function reject(CatalogGeneration|int $generation, ?string $reason = null, ?int $actorId = null): CatalogGeneration
    {
        $record = $generation instanceof CatalogGeneration
            ? $generation
            : CatalogGeneration::query()->findOrFail($generation);

        return $this->workflow->reject($record, $reason, $actorId);
    }

    public function review(CatalogGeneration|int $generation, ?int $actorId = null): CatalogGeneration
    {
        $record = $generation instanceof CatalogGeneration
            ? $generation
            : CatalogGeneration::query()->findOrFail($generation);

        return $this->workflow->submitForReview($record, $actorId);
    }

    public function edit(CatalogGeneration|int $generation, array $payload, ?int $actorId = null): CatalogGeneration
    {
        $record = $generation instanceof CatalogGeneration
            ? $generation
            : CatalogGeneration::query()->findOrFail($generation);

        return $this->workflow->edit($record, $payload, $actorId);
    }

    public function generator(): ListingGenerator
    {
        return $this->generator;
    }

    public function workflow(): WorkflowService
    {
        return $this->workflow;
    }

    public function costs(?string $tenantId = null, int $days = 30): array
    {
        return app(\ImranDevBd\AiCatalog\Support\CostTracker::class)->summary($tenantId, $days);
    }

    public function googleFeed(?int $limit = 100): array
    {
        $rows = \ImranDevBd\AiCatalog\Models\CatalogGeneration::query()
            ->where('status', \ImranDevBd\AiCatalog\Enums\GenerationStatus::Approved)
            ->latest('id')
            ->limit($limit ?? 100)
            ->get();

        return app(\ImranDevBd\AiCatalog\Support\FeedExporter::class)->google($rows);
    }

    public function agenticFeed(?int $limit = 100): array
    {
        $rows = \ImranDevBd\AiCatalog\Models\CatalogGeneration::query()
            ->where('status', \ImranDevBd\AiCatalog\Enums\GenerationStatus::Approved)
            ->latest('id')
            ->limit($limit ?? 100)
            ->get();

        return app(\ImranDevBd\AiCatalog\Support\AgenticFeedExporter::class)->agenticJson($rows);
    }

    public function llmsTxt(?int $limit = 100, bool $full = false): string
    {
        $rows = \ImranDevBd\AiCatalog\Models\CatalogGeneration::query()
            ->where('status', \ImranDevBd\AiCatalog\Enums\GenerationStatus::Approved)
            ->latest('id')
            ->limit($limit ?? 100)
            ->get();

        return app(\ImranDevBd\AiCatalog\Support\AgenticFeedExporter::class)->llmsTxt($rows, $full);
    }

    public function dppFeed(?int $limit = 100): array
    {
        $rows = \ImranDevBd\AiCatalog\Models\CatalogGeneration::query()
            ->where('status', \ImranDevBd\AiCatalog\Enums\GenerationStatus::Approved)
            ->latest('id')
            ->limit($limit ?? 100)
            ->get();

        $dppList = [];
        foreach ($rows as $row) {
            $listing = $row->listing();
            $dppList[] = [
                'id' => $row->uuid,
                'title' => $listing->title,
                'sku' => $listing->sku,
                'gtin' => $listing->gtin,
                'dpp' => $listing->dpp,
            ];
        }

        return $dppList;
    }

    public function audit(?string $tenantId = null, int $staleDays = 90): array
    {
        return app(\ImranDevBd\AiCatalog\Services\CatalogAuditor::class)->audit($tenantId, $staleDays);
    }
}
