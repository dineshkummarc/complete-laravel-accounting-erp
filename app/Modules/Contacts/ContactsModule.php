<?php

declare(strict_types=1);

namespace App\Modules\Contacts;

use App\Modules\Core\Modules\BaseModule;

final class ContactsModule extends BaseModule
{
    public function key(): string
    {
        return 'contacts';
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
