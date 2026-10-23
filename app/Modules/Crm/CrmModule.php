<?php

declare(strict_types=1);

namespace App\Modules\Crm;

use App\Modules\Core\Modules\BaseModule;

final class CrmModule extends BaseModule
{
    public function key(): string
    {
        return 'crm';
    }

    public function dependsOn(): array
    {
        return [];
    }

    public function optionalIntegrations(): array
    {
        return ['sales', 'messaging'];
    }

    public function isCore(): bool
    {
        return false;
    }
}
