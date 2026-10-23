<?php

declare(strict_types=1);

namespace App\Modules\Attendance;

use App\Modules\Core\Modules\BaseModule;

final class AttendanceModule extends BaseModule
{
    public function key(): string
    {
        return 'attendance';
    }

    public function dependsOn(): array
    {
        return ['hr'];
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
