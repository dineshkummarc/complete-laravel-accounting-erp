<?php

declare(strict_types=1);

namespace App\Modules\Api;

use App\Modules\Core\Modules\BaseModule;

final class ApiModule extends BaseModule
{
    public function key(): string
    {
        return 'api';
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
        return false;
    }
}
