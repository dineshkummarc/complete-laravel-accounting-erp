<?php

declare(strict_types=1);

namespace App\Modules\Projects;

use App\Modules\Core\Modules\BaseModule;

final class ProjectsModule extends BaseModule
{
    public function key(): string
    {
        return 'projects';
    }

    public function dependsOn(): array
    {
        return ['contacts'];
    }

    public function optionalIntegrations(): array
    {
        return ['sales', 'expenses', 'payroll'];
    }

    public function isCore(): bool
    {
        return false;
    }
}
