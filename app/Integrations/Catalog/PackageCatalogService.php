<?php

declare(strict_types=1);

namespace App\Integrations\Catalog;

use App\Integrations\Contracts\CatalogService;
use ImranDevBd\AiCatalog\Facades\AiCatalog;

final class PackageCatalogService implements CatalogService
{
    public function draftFromText(string $text): int
    {
        return (int) AiCatalog::fromText($text)->generate()->getKey();
    }
}
