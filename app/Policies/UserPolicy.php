<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Core\Services\BusinessContext;
use App\Modules\Core\Services\CompanyAccess;
use Illuminate\Support\Facades\DB;

final class UserPolicy
{
    public function view(User $actor, User $subject): bool
    {
        return $actor->id === $subject->id || app(CompanyAccess::class)->isOwner($actor) && DB::table('company_user')->where('company_id', app(BusinessContext::class)->companyId)->where('user_id', $subject->id)->exists();
    }

    public function update(User $actor, User $subject): bool
    {
        return $actor->id === $subject->id;
    }

    public function delete(User $actor, User $subject): bool
    {
        return false;
    }
}
