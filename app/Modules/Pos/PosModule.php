<?php

declare(strict_types=1);

namespace App\Modules\Pos;

use App\Modules\Core\Modules\BaseModule;

final class PosModule extends BaseModule
{
    public function key(): string
    {
        return 'pos';
    }

    public function dependsOn(): array
    {
        return ['sales'];
    }

    public function optionalIntegrations(): array
    {
        return ['inventory', 'banking'];
    }

    public function isCore(): bool
    {
        return false;
    }
}
