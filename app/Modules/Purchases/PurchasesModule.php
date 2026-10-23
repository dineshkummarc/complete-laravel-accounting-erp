<?php

declare(strict_types=1);

namespace App\Modules\Purchases;

use App\Modules\Core\Modules\BaseModule;

final class PurchasesModule extends BaseModule
{
    public function key(): string
    {
        return 'purchases';
    }

    public function dependsOn(): array
    {
        return ['contacts'];
    }

    public function optionalIntegrations(): array
    {
        return ['inventory', 'banking', 'tax'];
    }

    public function isCore(): bool
    {
        return false;
    }
}
