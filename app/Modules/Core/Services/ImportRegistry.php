<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Modules\Core\Contracts\ImportDefinition;
use InvalidArgumentException;

final class ImportRegistry
{
    public function get(string $key): ImportDefinition
    {
        return match ($key) {
            'branches' => app(BranchImport::class), default => throw new InvalidArgumentException('Unknown import definition.')
        };
    }
}
