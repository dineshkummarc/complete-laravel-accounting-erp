<?php

namespace ImranDevBd\AiCatalog\Http\Controllers;

use ImranDevBd\AiCatalog\Enums\GenerationStatus;
use ImranDevBd\AiCatalog\Models\CatalogBulkJob;
use ImranDevBd\AiCatalog\Models\CatalogGeneration;
use ImranDevBd\AiCatalog\Support\CostTracker;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class CatalogStudioController extends Controller
{
    public function index(CostTracker $costs): View
    {
        $summary = $costs->summary(null, 30);
        $recent = CatalogGeneration::query()->latest('id')->limit(20)->get();
        $queue = CatalogGeneration::query()
            ->whereIn('status', [GenerationStatus::Draft, GenerationStatus::InReview])
            ->latest('id')
            ->limit(20)
            ->get();
        $bulk = CatalogBulkJob::query()->latest('id')->limit(10)->get();

        return view('ai-catalog::studio', [
            'summary' => $summary,
            'recent' => $recent,
            'queue' => $queue,
            'bulk' => $bulk,
            'lowConfidence' => (float) config('ai-catalog.workflow.low_confidence_threshold', 0.7),
        ]);
    }

    public function llmsTxt(): \Illuminate\Http\Response
    {
        $content = \ImranDevBd\AiCatalog\Facades\AiCatalog::llmsTxt(200, true);

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
