<?php

declare(strict_types=1);

namespace App\Integrations\Fakes;

use App\Integrations\Contracts\AttendanceGateway;

final class FakeAttendanceGateway implements AttendanceGateway
{
    public function supportsPush(string $provider): bool
    {
        return $provider === 'adms';
    }
}
