<?php

declare(strict_types=1);

namespace App\Modules\Messaging;

use App\Modules\Core\Modules\BaseModule;

final class MessagingModule extends BaseModule
{
    public function key(): string
    {
        return 'messaging';
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
