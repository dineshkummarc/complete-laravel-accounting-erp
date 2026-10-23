<?php

declare(strict_types=1);

namespace App\Modules\Inventory;

use App\Modules\Core\Modules\BaseModule;

final class InventoryModule extends BaseModule
{
    public function key(): string
    {
        return 'inventory';
    }

    public function dependsOn(): array
    {
        return [];
    }

    public function optionalIntegrations(): array
    {
        return ['ai-assistant'];
    }

    public function isCore(): bool
    {
        return false;
    }
}
