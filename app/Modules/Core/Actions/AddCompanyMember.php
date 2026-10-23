<?php

declare(strict_types=1);

namespace App\Modules\Core\Actions;

use App\Models\User;
use App\Modules\Core\Services\AuditTrail;
use App\Modules\Core\Services\BusinessContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

final class AddCompanyMember
{
    /** @param list<int> $branches */
    public function handle(User $actor, string $email, array $branches, string $roleName = 'staff'): void
    {
        $context = app(BusinessContext::class);
        $company = $context->companyId;
        if (! DB::table('company_user')->where('company_id', $company)->where('user_id', $actor->id)->where('is_owner', true)->exists()) {
            throw new AuthorizationException('Only owners may manage company membership.');
        }
        $member = User::query()->where('email', $email)->firstOrFail();
        if ($member->id === $actor->id) {
            throw ValidationException::withMessages(['email' => 'Use a different member.']);
        }
        $valid = DB::table('branches')->where('company_id', $company)->whereNull('deleted_at')->where('is_active', true)->whereIn('id', $branches)->count();
        if ($branches === [] || $valid !== count(array_unique($branches))) {
            throw ValidationException::withMessages(['branches' => 'Select branches of this company.']);
        }
        if (! Role::query()->where('team_id', $company)->where('name', $roleName)->exists()) {
            throw ValidationException::withMessages(['role' => 'Unknown company role.']);
        }
        DB::transaction(function () use ($actor, $company, $member, $branches, $roleName): void {
            DB::table('company_user')->updateOrInsert(['company_id' => $company, 'user_id' => $member->id], ['is_owner' => $roleName === 'owner', 'created_at' => now(), 'updated_at' => now()]);
            $companyBranches = DB::table('branches')->where('company_id', $company)->pluck('id');
            DB::table('branch_user')->where('user_id', $member->id)->whereIn('branch_id', $companyBranches)->delete();
            foreach (array_unique($branches) as $branch) {
                DB::table('branch_user')->insert(['branch_id' => $branch, 'user_id' => $member->id]);
            }
            $role = Role::query()->where('team_id', $company)->where('name', $roleName)->firstOrFail();
            $member->syncRoles([$role]);
            app(AuditTrail::class)->record($company, null, $actor->id, 'membership.updated', User::class, (string) $member->id, [], ['branches' => $branches, 'role' => $roleName]);
        });
    }
}
