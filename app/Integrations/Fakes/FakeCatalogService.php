<?php

declare(strict_types=1);

namespace App\Integrations\Fakes;

use App\Integrations\Contracts\CatalogService;

final class FakeCatalogService implements CatalogService
{
    public function __construct(public int $draftId = 1) {}

    public function draftFromText(string $text): int
    {
        return $this->draftId;
    }
}
