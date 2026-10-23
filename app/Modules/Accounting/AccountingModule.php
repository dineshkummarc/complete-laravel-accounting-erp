<?php

declare(strict_types=1);

namespace App\Modules\Accounting;

use App\Modules\Core\Modules\BaseModule;

final class AccountingModule extends BaseModule
{
    public function key(): string
    {
        return 'accounting';
    }

    public function dependsOn(): array
    {
        return ['core'];
    }

    public function optionalIntegrations(): array
    {
        return [];
    }

    public function isCore(): bool
    {
        return true;
    }
}
