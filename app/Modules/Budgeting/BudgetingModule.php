<?php

declare(strict_types=1);

namespace App\Modules\Budgeting;

use App\Modules\Core\Modules\BaseModule;

final class BudgetingModule extends BaseModule
{
    public function key(): string
    {
        return 'budgeting';
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
