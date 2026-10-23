<?php

declare(strict_types=1);

namespace App\Modules\Hr;

use App\Modules\Core\Modules\BaseModule;

final class HrModule extends BaseModule
{
    public function key(): string
    {
        return 'hr';
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
