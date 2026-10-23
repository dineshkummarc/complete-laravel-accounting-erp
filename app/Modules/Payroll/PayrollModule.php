<?php

declare(strict_types=1);

namespace App\Modules\Payroll;

use App\Modules\Core\Modules\BaseModule;

final class PayrollModule extends BaseModule
{
    public function key(): string
    {
        return 'payroll';
    }

    public function dependsOn(): array
    {
        return ['hr'];
    }

    public function optionalIntegrations(): array
    {
        return ['attendance', 'banking', 'expenses'];
    }

    public function isCore(): bool
    {
        return false;
    }
}
