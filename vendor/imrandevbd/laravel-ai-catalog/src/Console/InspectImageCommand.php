<?php

namespace ImranDevBd\AiCatalog\Console;

use ImranDevBd\AiCatalog\Data\GenerationInput;
use ImranDevBd\AiCatalog\Enums\InputMode;
use ImranDevBd\AiCatalog\Image\ImageIntelligence;
use Illuminate\Console\Command;

class InspectImageCommand extends Command
{
    protected $signature = 'ai-catalog:inspect-image {path : Local image path or URL}';

    protected $description = 'Run image intelligence (hash, quality, duplicates, alt/OCR if enabled)';

    public function handle(ImageIntelligence $images): int
    {
        $path = (string) $this->argument('path');
        $report = $images->analyze($path, new GenerationInput(mode: InputMode::Image, image: $path));
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
