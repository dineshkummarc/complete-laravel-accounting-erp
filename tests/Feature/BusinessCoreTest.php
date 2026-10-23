<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Core\Actions\CreateCompany;
use App\Modules\Core\Models\Branch;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Modules\ModuleManager;
use App\Modules\Core\Modules\ModuleRegistry;
use App\Modules\Core\Services\BusinessContext;
use App\Modules\Core\Services\ModuleRuntime;
use App\Modules\Core\Services\NumberSequence;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function businessCompany(User $owner, string $name = 'বাংলা ব্যবসা'): Company
{
    return app(CreateCompany::class)->handle($owner, ['name' => $name, 'currency' => 'BDT', 'locale' => 'en', 'timezone' => 'Asia/Dhaka', 'localization_pack' => 'bd']);
}

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->company = businessCompany($this->owner);
    $this->branch = DB::table('branches')->where('company_id', $this->company->id)->value('id');
    app(BusinessContext::class)->select($this->owner->fresh(), $this->company->id, $this->branch);
});

it('creates an owner, headquarters, mandatory modules and chained audit event atomically', function () {
    expect($this->company->slug)->not->toBeEmpty();
    expect(Branch::query()->count())->toBe(1);
    expect(app(ModuleManager::class)->enabledKeys())->toBe(['core', 'accounting']);
    expect(DB::table('audit_events')->count())->toBe(1);
});

it('fails closed without a company context and isolates company master data', function () {
    $other = businessCompany(User::factory()->create(), 'Other company');
    expect(Branch::query()->count())->toBe(1);
    app(BusinessContext::class)->companyId = null;
    expect(Branch::query()->count())->toBe(0);
    expect(fn () => app(BusinessContext::class)->select($this->owner, $other->id, null))->toThrow(AuthorizationException::class);
});

it('requires explicit branch membership for non-owners', function () {
    $staff = User::factory()->create();
    DB::table('company_user')->insert(['company_id' => $this->company->id, 'user_id' => $staff->id, 'is_owner' => false]);
    expect(fn () => app(BusinessContext::class)->select($staff, $this->company->id, $this->branch))->toThrow(AuthorizationException::class);
    DB::table('branch_user')->insert(['branch_id' => $this->branch, 'user_id' => $staff->id]);
    app(BusinessContext::class)->select($staff, $this->company->id, $this->branch);
    expect(app(BusinessContext::class)->branchId)->toBe($this->branch);
});

it('restores context and permission team after a failing queued task', function () {
    expect(fn () => app(BusinessContext::class)->run(999, null, fn () => throw new RuntimeException('fail')))->toThrow(RuntimeException::class);
    expect(app(BusinessContext::class)->companyId)->toBe($this->company->id);
    expect(getPermissionsTeamId())->toBe($this->company->id);
});

it('supports audited off and on state for every optional manifest without deleting settings', function (string $key) {
    $manager = app(ModuleManager::class);
    $enable = function (string $module) use (&$enable, $manager): void {
        foreach (app(ModuleRegistry::class)->get($module)->dependsOn() as $dependency) {
            $enable($dependency);
        }
        if (! $manager->isEnabled($module)) {
            $manager->toggle($this->owner, $this->company->id, $module, true, 'Test enable');
        }
    };
    $enable($key);
    expect($manager->isEnabled($key))->toBeTrue();
    expect(app(ModuleRuntime::class)->run($key, $this->company->id, $this->branch, fn () => 'ran'))->toBe('ran');
    DB::table('company_modules')->where('company_id', $this->company->id)->where('module_key', $key)->update(['settings' => '{"kept":true}']);
    $manager->toggle($this->owner, $this->company->id, $key, false, 'Test off', $this->branch);
    expect($manager->isEnabled($key))->toBeFalse();
    expect(app(ModuleRuntime::class)->run($key, $this->company->id, $this->branch, fn () => throw new RuntimeException('Disabled work ran')))->toBeNull();
    expect($manager->isEnabled($key, $this->company->id))->toBeTrue();
    $manager->toggle($this->owner, $this->company->id, $key, true, 'Test branch restore', $this->branch);
    expect($manager->isEnabled($key))->toBeTrue();
    $manager->toggle($this->owner, $this->company->id, $key, false, 'Test company off');
    expect($manager->isEnabled($key))->toBeFalse();
    expect(fn () => $manager->toggle($this->owner, $this->company->id, $key, true, 'Illegal branch enable', $this->branch))->toThrow(ValidationException::class);
    $manager->toggle($this->owner, $this->company->id, $key, true, 'Test company restore');
    expect(DB::table('company_modules')->where('company_id', $this->company->id)->where('module_key', $key)->value('settings'))->toBe('{"kept":true}');
})->with(['contacts', 'sales', 'purchases', 'inventory', 'pos', 'banking', 'expenses', 'fixed-assets', 'hr', 'attendance', 'payroll', 'crm', 'projects', 'manufacturing', 'budgeting', 'tax', 'collections', 'messaging', 'documents', 'catalog-web', 'ai-assistant', 'api']);

it('rejects core disable and dependent-module disable', function () {
    $m = app(ModuleManager::class);
    expect(fn () => $m->toggle($this->owner, $this->company->id, 'core', false, 'test'))->toThrow(ValidationException::class);
    $m->toggle($this->owner, $this->company->id, 'contacts', true, 'test');
    $m->toggle($this->owner, $this->company->id, 'sales', true, 'test');
    expect(fn () => $m->toggle($this->owner, $this->company->id, 'contacts', false, 'test'))->toThrow(ValidationException::class);
});

it('returns web 404 and API 403 for disabled modules', function () {
    Route::middleware(['web', 'auth', 'module:sales'])->get('/module-probe', fn () => 'ok');
    $this->actingAs($this->owner->fresh())->get('/module-probe')->assertNotFound();
    $this->getJson('/module-probe')->assertForbidden();
    $m = app(ModuleManager::class);
    $m->toggle($this->owner, $this->company->id, 'contacts', true, 'test');
    $m->toggle($this->owner, $this->company->id, 'sales', true, 'test');
    $this->get('/module-probe')->assertOk();
});

it('rolls sequence numbers back with their enclosing document transaction', function () {
    $sequence = app(NumberSequence::class);
    try {
        DB::transaction(function () use ($sequence): void {
            $sequence->next('journal', 2026, 'JV');
            throw new RuntimeException('rollback');
        });
    } catch (RuntimeException) {
    }
    expect(DB::transaction(fn () => $sequence->next('journal', 2026, 'JV')))->toBe('JV-HQ-2026-000001');
    expect(DB::transaction(fn () => $sequence->next('journal', 2026, 'JV')))->toBe('JV-HQ-2026-000002');
});
