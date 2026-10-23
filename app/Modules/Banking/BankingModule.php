<?php

declare(strict_types=1);

namespace App\Modules\Banking;

use App\Modules\Core\Modules\BaseModule;

final class BankingModule extends BaseModule
{
    public function key(): string
    {
        return 'banking';
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
