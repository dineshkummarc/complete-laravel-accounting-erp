<?php

namespace ImranDevBd\AiCatalog\Jobs;

use ImranDevBd\AiCatalog\Enums\GenerationStatus;
use ImranDevBd\AiCatalog\Models\CatalogGeneration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RetryFailedGenerationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        CatalogGeneration::query()
            ->where('status', GenerationStatus::Failed)
            ->orderBy('id')
            ->each(function (CatalogGeneration $generation): void {
                $generation->status = GenerationStatus::Pending;
                $generation->error = null;
                $generation->save();

                GenerateListingJob::dispatch($generation->id)
                    ->onQueue((string) config('ai-catalog.queue', 'default'));
            });
    }
}
