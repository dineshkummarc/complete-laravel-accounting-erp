<?php

declare(strict_types=1);

namespace App\Modules\Core\Modules;

use App\Models\User;
use App\Modules\Core\Events\ModuleStateChanged;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Services\AuditTrail;
use App\Modules\Core\Services\BusinessContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ModuleManager
{
    /** @var array<int, array<string, bool|int>> */
    private array $states = [];

    /** @var array<string, array<string, bool|int>|null> */
    private array $branches = [];

    /** @var array<int, bool> */
    private array $companies = [];

    public function __construct(private readonly ModuleRegistry $registry) {}

    public function isEnabled(string $key, ?int $company = null, ?int $branch = null): bool
    {
        if ($company === null) {
            $company = app(BusinessContext::class)->companyId;
            $branch ??= app(BusinessContext::class)->branchId;
        }
        if ($company === null) {
            return false;
        }
        $module = $this->registry->get($key);
        $this->companies[$company] ??= DB::table('companies')->where('id', $company)->whereNull('deleted_at')->exists();
        if (! $this->companies[$company]) {
            return false;
        }
        if ($branch !== null) {
            $branchKey = $company.':'.$branch;
            if (! array_key_exists($branchKey, $this->branches)) {
                $this->branches[$branchKey] = DB::table('branches')->where('id', $branch)->where('company_id', $company)->whereNull('deleted_at')->exists()
                    ? DB::table('branch_modules')->where('branch_id', $branch)->pluck('enabled', 'module_key')->all() : null;
            }
            if ($this->branches[$branchKey] === null || array_key_exists($key, $this->branches[$branchKey]) && ! $this->branches[$branchKey][$key]) {
                return false;
            }
        }
        if ($module->isCore()) {
            return true;
        }
        $this->states[$company] ??= Cache::remember('modules:'.$company, 3600, fn (): array => DB::table('company_modules')->where('company_id', $company)->pluck('enabled', 'module_key')->all());
        if (! ($this->states[$company][$key] ?? false)) {
            return false;
        }
        foreach ($module->dependsOn() as $dependency) {
            if (! $this->isEnabled($dependency, $company, $branch)) {
                return false;
            }
        }

        return true;
    }

    /** @return list<string> */
    public function enabledKeys(?int $company = null, ?int $branch = null): array
    {
        return array_values(array_filter(array_keys($this->registry->all()), fn (string $key): bool => $this->isEnabled($key, $company, $branch)));
    }

    /** @return list<string> */
    public function dependentsOf(string $key): array
    {
        return array_keys(array_filter($this->registry->all(), fn (BaseModule $module): bool => in_array($key, $module->dependsOn(), true)));
    }

    /** @return list<string> */
    public function canDisable(int $company, string $key, ?int $branch = null): array
    {
        if ($this->registry->get($key)->isCore()) {
            return ['Core modules cannot be disabled.'];
        }

        return array_values(array_filter($this->dependentsOf($key), fn (string $dependent): bool => $this->isEnabled($dependent, $company, $branch)));
    }

    public function preset(User $actor, int $company, string $preset): void
    {
        $keys = config('presets.'.$preset);
        if (! is_array($keys)) {
            throw ValidationException::withMessages(['preset' => 'Unknown business preset.']);
        }
        DB::transaction(function () use ($actor, $company, $keys, $preset): void {
            foreach ($keys as $key) {
                if (! $this->isEnabled($key, $company)) {
                    $this->toggle($actor, $company, $key, true, 'Business preset: '.$preset);
                }
            }
        });
    }

    public function toggle(User $actor, int $company, string $key, bool $enabled, string $reason, ?int $branch = null): void
    {
        if (! DB::table('company_user')->where('company_id', $company)->where('user_id', $actor->id)->where('is_owner', true)->exists()) {
            throw new AuthorizationException('Only company owners may change modules.');
        }
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'A reason is required.']);
        }
        $module = $this->registry->get($key);
        DB::transaction(function () use ($actor, $company, $key, $enabled, $reason, $branch, $module): void {
            DB::table('companies')->where('id', $company)->lockForUpdate()->firstOrFail();
            unset($this->states[$company]);
            $this->branches = [];
            Cache::forget('modules:'.$company);
            if ($branch !== null && ! DB::table('branches')->where('id', $branch)->where('company_id', $company)->whereNull('deleted_at')->exists()) {
                throw new AuthorizationException('Branch belongs to another company.');
            }
            if (! $enabled && ($reasons = $this->canDisable($company, $key, $branch)) !== []) {
                throw ValidationException::withMessages(['module' => implode(', ', $reasons)]);
            }
            if ($enabled) {
                if ($branch !== null && ! $this->isEnabled($key, $company, null)) {
                    throw ValidationException::withMessages(['module' => 'Enable the module for the company first.']);
                }
                foreach ($module->dependsOn() as $dependency) {
                    if (! $this->isEnabled($dependency, $company, $branch)) {
                        throw ValidationException::withMessages(['module' => 'Required module: '.$dependency]);
                    }
                }
            }
            $tenant = Company::query()->findOrFail($company);
            if ($enabled) {
                $module->seedCompanyDefaults($tenant);
                $module->onEnable($tenant);
            } else {
                $module->onDisable($tenant);
            }
            if ($branch === null) {
                DB::table('company_modules')->updateOrInsert(['company_id' => $company, 'module_key' => $key], ['enabled' => $enabled,
                    $enabled ? 'enabled_at' : 'disabled_at' => now(), $enabled ? 'enabled_by' : 'disabled_by' => $actor->id, 'updated_at' => now()]);
            } elseif ($enabled) {
                DB::table('branch_modules')->where('branch_id', $branch)->where('module_key', $key)->delete();
            } else {
                DB::table('branch_modules')->updateOrInsert(['branch_id' => $branch, 'module_key' => $key], ['enabled' => false, 'created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('module_events')->insert(['company_id' => $company, 'branch_id' => $branch, 'module_key' => $key,
                'action' => $enabled ? 'enabled' : 'disabled', 'reason' => $reason, 'actor_id' => $actor->id, 'created_at' => now(), 'updated_at' => now()]);
            app(AuditTrail::class)->record($company, $branch, $actor->id, $enabled ? 'module.enabled' : 'module.disabled', 'module', $key, [], ['reason' => $reason]);
            unset($this->states[$company]);
            $this->branches = [];
            Cache::forget('modules:'.$company);
            DB::afterCommit(function () use ($company, $branch, $key, $enabled, $actor, $reason): void {
                Cache::forget('modules:'.$company);
                event(new ModuleStateChanged($company, $branch, $key, $enabled, $actor->id, $reason));
            });
        });
    }
}
