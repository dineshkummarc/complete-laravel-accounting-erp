<?php

declare(strict_types=1);

namespace App\Modules\Tax;

use App\Modules\Core\Modules\BaseModule;

final class TaxModule extends BaseModule
{
    public function key(): string
    {
        return 'tax';
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
