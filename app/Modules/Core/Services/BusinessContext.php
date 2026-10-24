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

    public ?int $actorId = null;

    /** @var array<string,bool> */
    private array $branchAccess = [];

    public function select(User $user, int $companyId, ?int $branchId): void
    {
        unset($this->branchAccess[$companyId.':'.$branchId.':'.$user->id]);
        app(CompanyAccess::class)->forget($user->id, $companyId);
        if (! DB::table('companies')->where('id', $companyId)->whereNull('deleted_at')->exists() || ! DB::table('company_user')->where('company_id', $companyId)->where('user_id', $user->id)->exists()) {
            throw new AuthorizationException('Company membership required.');
        }
        if ($branchId !== null && ! $this->canAccessBranch($user, $companyId, $branchId)) {
            throw new AuthorizationException('Branch membership required.');
        }
        $this->companyId = $companyId;
        $this->branchId = $branchId;
        $this->actorId = $user->id;
        setPermissionsTeamId($companyId);
        $user->unsetRelation('roles')->unsetRelation('permissions');
    }

    public function canAccessBranch(User $user, int $companyId, int $branchId): bool
    {
        $key = $companyId.':'.$branchId.':'.$user->id;
        if (array_key_exists($key, $this->branchAccess)) {
            return $this->branchAccess[$key];
        }
        $branch = DB::table('branches')->where('id', $branchId)->where('company_id', $companyId)->whereNull('deleted_at')->where('is_active', true)->exists();
        if (! $branch) {
            return false;
        }

        return $this->branchAccess[$key] = app(CompanyAccess::class)->isOwner($user, $companyId)
            || DB::table('branch_user')->where('branch_id', $branchId)->where('user_id', $user->id)->exists();
    }

    /** @template T
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(int $companyId, ?int $branchId, Closure $callback, ?int $actorId = null): mixed
    {
        $previous = [$this->companyId, $this->branchId, getPermissionsTeamId(), $this->actorId];
        $this->companyId = $companyId;
        $this->branchId = $branchId;
        setPermissionsTeamId($companyId);
        $this->actorId = $actorId ?? auth()->id();
        try {
            return $callback();
        } finally {
            [$this->companyId, $this->branchId] = $previous;
            setPermissionsTeamId($previous[2]);
            $this->actorId = $previous[3];
        }
    }
}
