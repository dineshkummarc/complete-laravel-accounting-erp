<?php

declare(strict_types=1);

namespace App\Integrations\Attendance;

use App\Integrations\Contracts\AttendanceGateway;
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;

final class PackageAttendanceGateway implements AttendanceGateway
{
    public function supportsPush(string $provider): bool
    {
        return AttendanceHub::provider($provider)->supportsPush();
    }
}
