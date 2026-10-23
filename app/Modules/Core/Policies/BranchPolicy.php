<?php

declare(strict_types=1);

namespace App\Modules\Core\Policies;

use App\Models\User;
use App\Modules\Core\Models\Branch;
use App\Modules\Core\Services\BusinessContext;
use Illuminate\Support\Facades\DB;

final class BranchPolicy
{
    public function view(User $user, Branch $branch): bool
    {
        return $branch->company_id === app(BusinessContext::class)->companyId && app(BusinessContext::class)->canAccessBranch($user, $branch->company_id, $branch->id);
    }

    public function create(User $user): bool
    {
        return DB::table('company_user')->where('company_id', app(BusinessContext::class)->companyId)->where('user_id', $user->id)->where('is_owner', true)->exists();
    }

    public function update(User $user, Branch $branch): bool
    {
        return $branch->company_id === app(BusinessContext::class)->companyId && $this->create($user);
    }

    public function delete(User $user, Branch $branch): bool
    {
        return false;
    }
}
