<?php

namespace ImranDevBd\AiCatalog\Console;

use ImranDevBd\AiCatalog\Facades\AiCatalog;
use Illuminate\Console\Command;

class AgenticFeedCommand extends Command
{
    protected $signature = 'ai-catalog:agentic-feed {--output= : Optional file path to write output} {--full : Generate full deep catalog with Q&A and DPP} {--limit=100 : Maximum listings to include}';

    protected $description = 'Export llms.txt standard catalog feed for autonomous AI shopping agents and Generative Engine Optimization (GEO)';

    public function handle(): int
    {
        $output = $this->option('output');
        $full = (bool) $this->option('full');
        $limit = (int) $this->option('limit');

        $this->info('Generating agentic llms.txt feed...');

        $content = AiCatalog::llmsTxt($limit, $full);

        if ($output) {
            $dir = dirname($output);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($output, $content);
            $this->info("Saved agentic feed to: {$output}");
        } else {
            $this->line($content);
        }

        return self::SUCCESS;
    }
}
