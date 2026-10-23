<?php

declare(strict_types=1);

namespace App\Integrations\Contracts;

interface CatalogService
{
    public function draftFromText(string $text): int;
}
