<?php

namespace ImranDevBd\AiCatalog\Console;

use ImranDevBd\AiCatalog\Facades\AiCatalog;
use ImranDevBd\AiCatalog\Support\FeedExporter;
use Illuminate\Console\Command;

class ExportFeedCommand extends Command
{
    protected $signature = 'ai-catalog:export-feed {--format=google : google|facebook} {--output=}';

    protected $description = 'Export approved listings to Google Shopping JSON or Facebook catalog CSV';

    public function handle(FeedExporter $exporter): int
    {
        $rows = collect(AiCatalog::googleFeed(500));
        $format = (string) $this->option('format');

        if ($format === 'facebook') {
            $csv = $exporter->facebookCsv(
                \ImranDevBd\AiCatalog\Models\CatalogGeneration::query()
                    ->where('status', \ImranDevBd\AiCatalog\Enums\GenerationStatus::Approved)
                    ->limit(500)
                    ->get()
            );
            $path = $this->option('output') ?: storage_path('app/ai-catalog-facebook.csv');
            file_put_contents($path, $csv);
            $this->info("Wrote {$path}");

            return self::SUCCESS;
        }

        $path = $this->option('output') ?: storage_path('app/ai-catalog-google.json');
        file_put_contents($path, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info('Wrote '.$path.' ('.$rows->count().' rows)');

        return self::SUCCESS;
    }
}
