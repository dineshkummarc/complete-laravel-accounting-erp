<?php

namespace ImranDevBd\AiCatalog\Console;

use ImranDevBd\AiCatalog\Services\BulkImporter;
use Illuminate\Console\Command;

class BulkCommand extends Command
{
    protected $signature = 'ai-catalog:bulk {file : Path to CSV, Excel-exported CSV, or JSON}';

    protected $description = 'Queue mass AI catalog generation from a file';

    public function handle(BulkImporter $importer): int
    {
        $file = (string) $this->argument('file');
        $job = $importer->queue($file);

        $this->info("Queued bulk job #{$job->id} [{$job->uuid}] ({$job->total} rows)");

        return self::SUCCESS;
    }
}
