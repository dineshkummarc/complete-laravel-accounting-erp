<?php

declare(strict_types=1);

use App\Filament\Pages\Branches;
use App\Filament\Pages\CompanySettings;
use App\Filament\Pages\Modules;
use App\Models\User;
use App\Modules\Core\Actions\AddAttachment;
use App\Modules\Core\Actions\AddCompanyMember;
use App\Modules\Core\Actions\CreateBranch;
use App\Modules\Core\Actions\ExportBranches;
use App\Modules\Core\Actions\QueueImport;
use App\Modules\Core\Jobs\ProcessImport;
use App\Modules\Core\Models\Attachment;
use App\Modules\Core\Models\Branch;
use App\Modules\Core\Modules\ModuleManager;
use App\Modules\Core\Services\AuditTrail;
use App\Modules\Core\Services\BusinessContext;
use App\Modules\Core\Services\ImportRegistry;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->company = businessCompany($this->owner);
    $this->branchId = $this->owner->fresh()->current_branch_id;
    $this->actingAs($this->owner->fresh());
    app(BusinessContext::class)->select(auth()->user(), $this->company->id, $this->branchId);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($this->company);
    Storage::fake('local');
});

it('renders company settings and role management only within authorized tenancy', function () {
    $this->get('/app/'.$this->company->slug.'/branches')->assertOk();
    $this->get('/app/'.$this->company->slug.'/modules')->assertOk();
    $this->get('/app/'.$this->company->slug.'/shield/roles')->assertOk();
    $other = businessCompany(User::factory()->create(), 'Private Company');
    $this->get('/app/'.$other->slug.'/branches')->assertNotFound();
});

it('creates branches through the Livewire action and validates duplicate codes', function () {
    Livewire::test(Branches::class)->callAction('create', ['name' => 'চট্টগ্রাম', 'code' => 'CTG', 'timezone' => 'Asia/Dhaka'])->assertHasNoActionErrors();
    expect(Branch::query()->where('code', 'CTG')->exists())->toBeTrue();
    Livewire::test(Branches::class)->callAction('create', ['name' => 'Duplicate', 'code' => 'CTG', 'timezone' => 'Asia/Dhaka'])->assertHasActionErrors(['code']);
});

it('guards dependent toggles through the Livewire module action', function () {
    Livewire::test(Modules::class)->callAction('change', ['key' => 'sales', 'enabled' => true, 'branch_only' => false, 'reason' => 'Enable'])->assertHasActionErrors(['key']);
    Livewire::test(Modules::class)->callAction('change', ['key' => 'contacts', 'enabled' => true, 'branch_only' => false, 'reason' => 'Enable'])->assertHasNoActionErrors();
    expect(app(ModuleManager::class)->isEnabled('contacts'))->toBeTrue();
});

it('stores private uploads with checksums and requires signed authorized downloads', function () {
    $branch = Branch::query()->findOrFail($this->branchId);
    $file = UploadedFile::fake()->createWithContent('notes.txt', 'Private branch note');
    $attachment = app(AddAttachment::class)->handle(auth()->user(), $branch, $file);
    expect($attachment->sha256)->toBe(hash('sha256', 'Private branch note'));
    Storage::disk('local')->assertExists($attachment->path);
    $this->get('/business/attachments/'.$attachment->uuid)->assertForbidden();
    $url = URL::temporarySignedRoute('business.attachment', now()->addMinutes(5), ['uuid' => $attachment->uuid]);
    $this->get($url)->assertOk()->assertDownload('notes.txt');
    $other = businessCompany(User::factory()->create(), 'Other');
    $staff = User::factory()->create();
    $this->actingAs($staff)->get($url)->assertNotFound();
});

it('rejects executable uploads before storing any file', function () {
    $branch = Branch::query()->findOrFail($this->branchId);
    expect(fn () => app(AddAttachment::class)->handle(auth()->user(), $branch, UploadedFile::fake()->createWithContent('payload.txt', '<?php echo 1;')))->toThrow(ValidationException::class);
    expect(Attachment::query()->count())->toBe(0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

it('imports CSV rows with per-row validation, progress and duplicate-delivery protection', function () {
    Queue::fake();
    $file = UploadedFile::fake()->createWithContent('branches.csv', "name,code,timezone\nDhaka,DHK,Asia/Dhaka\nDuplicate,DHK,Asia/Dhaka\nKhulna,KHL,Asia/Dhaka\n");
    $batch = app(QueueImport::class)->handle(auth()->user(), 'branches', $file);
    $job = new ProcessImport($this->company->id, $this->branchId, $batch->id);
    $job->handle(app(BusinessContext::class), app(ImportRegistry::class));
    $job->handle(app(BusinessContext::class), app(ImportRegistry::class));
    $batch->refresh();
    expect($batch->status)->toBe('completed_with_errors');
    expect($batch->processed)->toBe(3);
    expect($batch->failed)->toBe(1);
    expect(Branch::query()->count())->toBe(3);
});

it('escapes spreadsheet formula injection in CSV exports', function () {
    app(CreateBranch::class)->handle(auth()->user(), ['name' => '=HYPERLINK("https://invalid")', 'code' => 'SAFE', 'timezone' => 'UTC']);
    $stream = fopen('php://temp', 'w+');
    app(ExportBranches::class)->handle(auth()->user(), $stream);
    rewind($stream);
    fgetcsv($stream, null, ',', '"', '');
    fgetcsv($stream, null, ',', '"', '');
    expect(fgetcsv($stream, null, ',', '"', '')[0])->toStartWith("'=");
    fclose($stream);
});

it('verifies audit chains and detects altered content', function () {
    app(CreateBranch::class)->handle(auth()->user(), ['name' => 'Sylhet', 'code' => 'SYL', 'timezone' => 'Asia/Dhaka']);
    expect(app(AuditTrail::class)->verify($this->company->id))->toBeTrue();
    DB::table('audit_events')->where('company_id', $this->company->id)->orderByDesc('id')->limit(1)->update(['action' => 'tampered']);
    expect(app(AuditTrail::class)->verify($this->company->id))->toBeFalse();
});

it('assigns staff only to chosen branches and prevents cross-company membership changes', function () {
    $staff = User::factory()->create();
    app(AddCompanyMember::class)->handle(auth()->user(), $staff->email, [$this->branchId]);
    app(BusinessContext::class)->select($staff, $this->company->id, $this->branchId);
    expect(fn () => app(CreateBranch::class)->handle($staff, ['name' => 'Forbidden', 'code' => 'NO', 'timezone' => 'UTC']))->toThrow(AuthorizationException::class);
    $this->actingAs($staff)->get('/app/'.$this->company->slug.'/team')->assertForbidden();
});

it('applies every preset with its dependency graph and role permissions', function (string $preset) {
    app(ModuleManager::class)->preset(auth()->user(), $this->company->id, $preset);
    foreach (config('presets.'.$preset) as $module) {
        expect(app(ModuleManager::class)->isEnabled($module))->toBeTrue();
    }
})->with(['retail', 'wholesale', 'manufacturing', 'service', 'restaurant', 'freelancer']);

it('audits master-data updates with the installed auditing package and company resolver', function () {
    config(['audit.console' => true]);
    app(CreateBranch::class)->handle(auth()->user(), ['name' => 'Audited', 'code' => 'AUDIT', 'timezone' => 'UTC']);
    expect(DB::table('audits')->where('company_id', $this->company->id)->where('auditable_type', Branch::class)->exists())->toBeTrue();
});

it('does not query once per module while rendering the module cards', function () {
    DB::enableQueryLog();
    DB::flushQueryLog();
    (new Modules)->moduleCards();
    expect(count(DB::getQueryLog()))->toBeLessThan(8);
    DB::disableQueryLog();
});

it('preserves platform permissions after selecting a tenant', function () {
    Permission::findOrCreate('platform.console', 'web');
    setPermissionsTeamId(0);
    auth()->user()->givePermissionTo('platform.console');
    app(BusinessContext::class)->select(auth()->user(), $this->company->id, $this->branchId);
    expect(auth()->user()->can('platform.console'))->toBeTrue();
    expect(getPermissionsTeamId())->toBe($this->company->id);
});

it('updates company settings through the action without renaming the tenant URL', function () {
    $slug = $this->company->slug;
    Livewire::test(CompanySettings::class)
        ->fillForm(['name' => 'নতুন নাম', 'locale' => 'bn', 'timezone' => 'Asia/Dhaka', 'fiscal_year_start_month' => 7])
        ->call('save')->assertHasNoFormErrors();
    $this->company->refresh();
    expect($this->company->name)->toBe('নতুন নাম');
    expect($this->company->slug)->toBe($slug);
});

it('keeps staff outside company role administration', function () {
    $staff = User::factory()->create();
    app(AddCompanyMember::class)->handle(auth()->user(), $staff->email, [$this->branchId]);
    $this->actingAs($staff)->get('/app/'.$this->company->slug.'/shield/roles')->assertForbidden();
});

it('detects changes to the recorded audit timestamp', function () {
    DB::table('audit_events')->where('company_id', $this->company->id)->update(['created_at' => '2020-01-01 00:00:00']);
    expect(app(AuditTrail::class)->verify($this->company->id))->toBeFalse();
});
