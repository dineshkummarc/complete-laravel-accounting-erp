<?php

declare(strict_types=1);

namespace App\Integrations\Console;

use App\Integrations\Contracts\AdminPanelAdapter;
use App\Models\User;

final class PackageAdminPanelAdapter implements AdminPanelAdapter
{
    public function allows(User $user): bool
    {
        return $user->can('platform.console');
    }
}
