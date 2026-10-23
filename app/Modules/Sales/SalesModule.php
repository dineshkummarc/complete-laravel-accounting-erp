<?php

declare(strict_types=1);

namespace App\Modules\Sales;

use App\Modules\Core\Modules\BaseModule;

final class SalesModule extends BaseModule
{
    public function key(): string
    {
        return 'sales';
    }

    public function dependsOn(): array
    {
        return ['contacts'];
    }

    public function optionalIntegrations(): array
    {
        return ['inventory', 'banking', 'tax', 'ai-assistant'];
    }

    public function isCore(): bool
    {
        return false;
    }
}
