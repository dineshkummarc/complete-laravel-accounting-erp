<?php

declare(strict_types=1);

namespace App\Integrations\Contracts;

interface DrutogotiAdapter
{
    /** @return array<string, int|bool> */
    public function summary(): array;
}
