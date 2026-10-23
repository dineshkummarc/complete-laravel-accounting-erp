<?php

declare(strict_types=1);

namespace App\Integrations\Contracts;

use App\Models\User;

interface AdminPanelAdapter
{
    public function allows(User $user): bool;
}
