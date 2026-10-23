<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Models\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class BusinessContext
{
    public ?int $companyId = null;

    public ?int $branchId = null;

    public function select(User $user, int $companyId, ?int $branchId): void
    {
        if (! DB::table('companies')->where('id', $companyId)->whereNull('deleted_at')->exists() || ! DB::table('company_user')->where('company_id', $companyId)->where('user_id', $user->id)->exists()) {
            throw new AuthorizationException('Company membership required.');
        }
        if ($branchId !== null && ! $this->canAccessBranch($user, $companyId, $branchId)) {
            throw new AuthorizationException('Branch membership required.');
        }
        $this->companyId = $companyId;
        $this->branchId = $branchId;
        setPermissionsTeamId($companyId);
        $user->unsetRelation('roles')->unsetRelation('permissions');
    }

    public function canAccessBranch(User $user, int $companyId, int $branchId): bool
    {
        $branch = DB::table('branches')->where('id', $branchId)->where('company_id', $companyId)->whereNull('deleted_at')->where('is_active', true)->exists();
        if (! $branch) {
            return false;
        }

        return app(CompanyAccess::class)->isOwner($user, $companyId)
            || DB::table('branch_user')->where('branch_id', $branchId)->where('user_id', $user->id)->exists();
    }

    /** @template T
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(int $companyId, ?int $branchId, Closure $callback): mixed
    {
        $previous = [$this->companyId, $this->branchId, getPermissionsTeamId()];
        $this->companyId = $companyId;
        $this->branchId = $branchId;
        setPermissionsTeamId($companyId);
        try {
            return $callback();
        } finally {
            [$this->companyId, $this->branchId] = $previous;
            setPermissionsTeamId($previous[2]);
        }
    }
}
