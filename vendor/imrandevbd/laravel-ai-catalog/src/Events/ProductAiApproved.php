<?php

namespace ImranDevBd\AiCatalog\Events;

use ImranDevBd\AiCatalog\Models\CatalogGeneration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductAiApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CatalogGeneration $generation,
        public ?Model $product = null,
    ) {}
}
