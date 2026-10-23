<?php

declare(strict_types=1);

namespace App\Modules\Expenses;

use App\Modules\Core\Modules\BaseModule;

final class ExpensesModule extends BaseModule
{
    public function key(): string
    {
        return 'expenses';
    }

    public function dependsOn(): array
    {
        return [];
    }

    public function optionalIntegrations(): array
    {
        return ['ai-assistant', 'banking'];
    }

    public function isCore(): bool
    {
        return false;
    }
}
