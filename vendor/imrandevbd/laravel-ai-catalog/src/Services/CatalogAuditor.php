<?php

namespace ImranDevBd\AiCatalog\Services;

use ImranDevBd\AiCatalog\Enums\GenerationStatus;
use ImranDevBd\AiCatalog\Models\CatalogGeneration;
use Illuminate\Support\Carbon;

class CatalogAuditor
{
    /**
     * Audit catalog generations and return a detailed health scorecard and repair recommendations.
     *
     * @return array{
     *     health_score: int,
     *     total_audited: int,
     *     healthy_count: int,
     *     attention_count: int,
     *     issues: list<array<string, mixed>>,
     *     healable_ids: list<int>
     * }
     */
    public function audit(?string $tenantId = null, int $staleDays = 90): array
    {
        $query = CatalogGeneration::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId));

        $total = $query->count();
        if ($total === 0) {
            return [
                'health_score' => 100,
                'total_audited' => 0,
                'healthy_count' => 0,
                'attention_count' => 0,
                'issues' => [],
                'healable_ids' => [],
            ];
        }

        $records = $query->get();
        $issues = [];
        $healableIds = [];
        $healthyCount = 0;
        $staleDate = Carbon::now()->subDays($staleDays);

        foreach ($records as $record) {
            $listing = $record->listing();
            $itemIssues = [];

            // 1. Check title & meta length
            $titleLen = mb_strlen($listing->title);
            if ($titleLen < 20 || $titleLen > 100) {
                $itemIssues[] = "Title length ({$titleLen} chars) suboptimal for SEO/GEO (ideal: 30-70).";
            }

            $metaLen = mb_strlen($listing->metaDescription);
            if ($metaLen < 80 || $metaLen > 170) {
                $itemIssues[] = "Meta description length ({$metaLen} chars) out of optimal 120-160 range.";
            }

            // 2. Check specs completeness
            $coreSpecs = ['materials', 'color', 'size', 'dimensions'];
            $missingSpecs = [];
            foreach ($coreSpecs as $cs) {
                if (empty($listing->specs[$cs])) {
                    $missingSpecs[] = $cs;
                }
            }
            if (count($missingSpecs) >= 2) {
                $itemIssues[] = 'Missing core commerce specs: '.implode(', ', $missingSpecs).'.';
            }

            // 3. Stale listing check
            if ($record->status === GenerationStatus::Approved && $record->updated_at && $record->updated_at->lt($staleDate)) {
                $itemIssues[] = "Listing has not been refreshed in >{$staleDays} days.";
            }

            // 4. Low confidence
            if ($record->overall_confidence !== null && $record->overall_confidence < 0.75) {
                $itemIssues[] = "Overall confidence low: ".round($record->overall_confidence * 100)."%.";
            }

            // 5. Unmatched category or flags
            if (in_array('unmatched_category', $record->flags ?? [])) {
                $itemIssues[] = 'Unmatched category requires taxonomy assignment.';
            }

            if ($itemIssues !== []) {
                $issues[] = [
                    'id' => $record->id,
                    'uuid' => $record->uuid,
                    'title' => $listing->title ?: 'Untitled',
                    'status' => $record->status instanceof GenerationStatus ? $record->status->value : (string) $record->status,
                    'issues' => $itemIssues,
                ];
                $healableIds[] = $record->id;
            } else {
                $healthyCount++;
            }
        }

        $healthScore = (int) round(($healthyCount / max(1, $total)) * 100);

        return [
            'health_score' => $healthScore,
            'total_audited' => $total,
            'healthy_count' => $healthyCount,
            'attention_count' => count($issues),
            'issues' => $issues,
            'healable_ids' => $healableIds,
        ];
    }
}
