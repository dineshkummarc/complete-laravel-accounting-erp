<?php

declare(strict_types=1);

namespace App\Modules\Core;

use App\Modules\Core\Modules\BaseModule;

final class CoreModule extends BaseModule
{
    public function key(): string
    {
        return 'core';
    }

    public function dependsOn(): array
    {
        return [];
    }

    public function optionalIntegrations(): array
    {
        return [];
    }

    public function isCore(): bool
    {
        return true;
    }
}
