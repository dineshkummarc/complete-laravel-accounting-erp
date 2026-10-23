<?php

declare(strict_types=1);

namespace App\Integrations\Contracts;

interface AttendanceGateway
{
    public function supportsPush(string $provider): bool;
}
