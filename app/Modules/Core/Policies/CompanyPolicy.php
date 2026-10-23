<?php

declare(strict_types=1);

namespace App\Modules\Core\Policies;

use App\Models\User;
use App\Modules\Core\Models\Company;

final class CompanyPolicy
{
    public function create(User $user): bool
    {
        return $user->exists;
    }

    public function view(User $user, Company $company): bool
    {
        return $user->canAccessTenant($company);
    }

    public function update(User $user, Company $company): bool
    {
        return $company->users()->where('users.id', $user->id)->wherePivot('is_owner', true)->exists();
    }

    public function delete(User $user, Company $company): bool
    {
        return false;
    }
}
