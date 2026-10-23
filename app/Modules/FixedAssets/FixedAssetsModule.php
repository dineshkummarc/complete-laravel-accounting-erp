<?php

declare(strict_types=1);

namespace App\Modules\FixedAssets;

use App\Modules\Core\Modules\BaseModule;

final class FixedAssetsModule extends BaseModule
{
    public function key(): string
    {
        return 'fixed-assets';
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
