<?php

namespace ImranDevBd\AiCatalog\Events;

use ImranDevBd\AiCatalog\Models\CatalogGeneration;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductAiReviewed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CatalogGeneration $generation,
        public string $decision,
    ) {}
}
