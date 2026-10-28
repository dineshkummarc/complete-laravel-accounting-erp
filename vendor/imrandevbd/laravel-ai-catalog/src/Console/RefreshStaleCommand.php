<?php

namespace ImranDevBd\AiCatalog\Console;

use ImranDevBd\AiCatalog\Enums\GenerationStatus;
use ImranDevBd\AiCatalog\Jobs\GenerateListingJob;
use ImranDevBd\AiCatalog\Models\CatalogGeneration;
use Illuminate\Console\Command;

class RefreshStaleCommand extends Command
{
    protected $signature = 'ai-catalog:refresh-stale {--days=}';

    protected $description = 'Re-queue approved listings older than the stale window';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('ai-catalog.workflow.stale_after_days', 90));
        $count = 0;

        CatalogGeneration::query()
            ->where('status', GenerationStatus::Approved)
            ->where('updated_at', '<', now()->subDays($days))
            ->each(function (CatalogGeneration $generation) use (&$count): void {
                $generation->status = GenerationStatus::Pending;
                $generation->save();
                GenerateListingJob::dispatch($generation->id)->onQueue((string) config('ai-catalog.queue', 'default'));
                $count++;
            });

        $this->info("Queued {$count} stale listing(s) for refresh.");

        return self::SUCCESS;
    }
}
