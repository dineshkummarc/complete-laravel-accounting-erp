<?php

declare(strict_types=1);

namespace App\Modules\CatalogWeb;

use App\Modules\Core\Modules\BaseModule;

final class CatalogWebModule extends BaseModule
{
    public function key(): string
    {
        return 'catalog-web';
    }

    public function dependsOn(): array
    {
        return ['inventory'];
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
