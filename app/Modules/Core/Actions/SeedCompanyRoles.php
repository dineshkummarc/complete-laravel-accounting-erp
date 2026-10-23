<?php

declare(strict_types=1);

namespace App\Modules\Core\Actions;

use App\Models\User;
use App\Modules\Core\Modules\ModuleRegistry;
use App\Modules\Core\Services\BusinessContext;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class SeedCompanyRoles
{
    public function handle(User $owner, int $company): void
    {
        app(BusinessContext::class)->run($company, null, function () use ($owner, $company): void {
            $permissions = [];
            foreach (app(ModuleRegistry::class)->all() as $module) {
                foreach ($module->permissions() as $permission) {
                    $permissions[] = Permission::findOrCreate($permission, 'web');
                }
            }
            $role = Role::firstOrCreate(['name' => 'owner', 'team_id' => $company, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
            $owner->assignRole($role);
            Role::firstOrCreate(['name' => 'staff', 'team_id' => $company, 'guard_name' => 'web']);
        });
        $owner->unsetRelation('roles')->unsetRelation('permissions');
    }
}
