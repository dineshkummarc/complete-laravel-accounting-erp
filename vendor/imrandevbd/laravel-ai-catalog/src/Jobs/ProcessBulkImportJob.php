<?php

namespace ImranDevBd\AiCatalog\Jobs;

use ImranDevBd\AiCatalog\Models\CatalogBulkJob;
use ImranDevBd\AiCatalog\Services\BulkImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessBulkImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $bulkJobId) {}

    public function handle(BulkImporter $importer): void
    {
        $job = CatalogBulkJob::query()->findOrFail($this->bulkJobId);
        $importer->process($job);
    }
}
