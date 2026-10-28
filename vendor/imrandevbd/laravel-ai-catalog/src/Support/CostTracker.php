<?php

namespace ImranDevBd\AiCatalog\Support;

use ImranDevBd\AiCatalog\Exceptions\AiCatalogException;
use ImranDevBd\AiCatalog\Models\CatalogGeneration;

class CostTracker
{
    /**
     * @return array{requests:int,tokens:int,cost_usd:float,failed:int,by_provider:array<string,float>}
     */
    public function summary(?string $tenantId = null, int $days = 30): array
    {
        $query = CatalogGeneration::query()->where('created_at', '>=', now()->subDays($days));
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $rows = $query->get(['cost_usd', 'prompt_tokens', 'completion_tokens', 'provider', 'status']);
        $byProvider = [];
        foreach ($rows as $row) {
            $name = (string) ($row->provider ?: 'unknown');
            $byProvider[$name] = round(($byProvider[$name] ?? 0) + (float) $row->cost_usd, 6);
        }

        return [
            'requests' => $rows->count(),
            'tokens' => (int) $rows->sum(fn ($r) => (int) $r->prompt_tokens + (int) $r->completion_tokens),
            'cost_usd' => round((float) $rows->sum('cost_usd'), 6),
            'failed' => $rows->where('status', \ImranDevBd\AiCatalog\Enums\GenerationStatus::Failed)->count(),
            'by_provider' => $byProvider,
        ];
    }

    public function assertWithinBudget(): void
    {
        if (! config('ai-catalog.budget.enabled')) {
            return;
        }

        $cap = (float) config('ai-catalog.budget.monthly_usd', 0);
        if ($cap <= 0) {
            return;
        }

        $spent = $this->summary(null, 31)['cost_usd'];
        if ($spent < $cap) {
            return;
        }

        if (config('ai-catalog.budget.on_exceed') === 'block') {
            throw new AiCatalogException("AI Catalog monthly budget of \${$cap} USD exceeded (spent \${$spent}).");
        }
    }
}
