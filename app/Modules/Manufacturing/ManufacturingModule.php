<?php

declare(strict_types=1);

namespace App\Modules\Manufacturing;

use App\Modules\Core\Modules\BaseModule;

final class ManufacturingModule extends BaseModule
{
    public function key(): string
    {
        return 'manufacturing';
    }

    public function dependsOn(): array
    {
        return ['inventory'];
    }

    public function optionalIntegrations(): array
    {
        return ['hr', 'payroll', 'fixed-assets'];
    }

    public function isCore(): bool
    {
        return false;
    }
}
