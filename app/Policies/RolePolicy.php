<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Core\Services\BusinessContext;
use App\Modules\Core\Services\CompanyAccess;
use Spatie\Permission\Models\Role;

final class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->create($user);
    }

    public function view(User $user, Role $role): bool
    {
        return $this->update($user, $role);
    }

    public function create(User $user): bool
    {
        return app(CompanyAccess::class)->isOwner($user);
    }

    public function update(User $user, Role $role): bool
    {
        return $role->getAttribute('team_id') === app(BusinessContext::class)->companyId && $this->create($user);
    }

    public function delete(User $user, Role $role): bool
    {
        return ! in_array($role->name, ['owner', 'staff'], true) && $this->update($user, $role);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
