<?php

namespace ImranDevBd\AiCatalog\Jobs;

use ImranDevBd\AiCatalog\Enums\GenerationStatus;
use ImranDevBd\AiCatalog\Models\CatalogGeneration;
use ImranDevBd\AiCatalog\Services\ListingGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateListingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $generationId) {}

    public function handle(ListingGenerator $generator): void
    {
        $record = CatalogGeneration::query()->findOrFail($this->generationId);

        if (in_array($record->status, [GenerationStatus::Approved, GenerationStatus::Published], true)) {
            return;
        }

        $generator->generate($generator->inputFromGeneration($record), $record);
    }
}
