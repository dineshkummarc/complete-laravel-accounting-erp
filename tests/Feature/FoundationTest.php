<?php

declare(strict_types=1);

use App\Integrations\Contracts;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use ImranDev\UnicodePdf\Facades\UnicodePdf;
use ImranDev\UniversalSlug\UniversalSlug;
use ImranDevBd\AiHub\Facades\AIHub;
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();
    setPermissionsTeamId(0);
});

it('boots every installed integration through its container binding', function () {
    foreach ([Contracts\AiGateway::class, Contracts\CatalogService::class, Contracts\PdfRenderer::class, Contracts\AttendanceGateway::class, Contracts\AdminPanelAdapter::class, Contracts\DrutogotiAdapter::class] as $contract) {
        expect(app($contract))->toBeInstanceOf($contract);
    }
    expect(app(Contracts\DrutogotiAdapter::class)->summary()['requests'])->toBe(0);
    expect(UniversalSlug::generate('বাংলা হিসাব'))->not->toBeEmpty();
});

it('protects all management screens from guests', function (string $path) {
    $this->get($path)->assertRedirect('/app/login');
})->with(['/app', '/admin', '/drutogoti', '/ai-hub', '/ai-catalog']);

it('forbids an ordinary user from the console and studios', function (string $path) {
    $this->actingAs(User::factory()->create())->get($path)->assertForbidden();
})->with(['/admin', '/drutogoti', '/ai-hub', '/ai-catalog']);

it('shares one authenticated session across both independent panels', function () {
    $user = User::factory()->create();
    Permission::create(['name' => 'platform.console', 'guard_name' => 'web']);
    Permission::create(['name' => 'app.access', 'guard_name' => 'web']);
    $user->givePermissionTo(['platform.console', 'app.access']);
    $this->actingAs($user)->get('/app')->assertOk();
    $this->get('/admin')->assertOk()->assertDontSee('$48,250.00')->assertSee('Platform Console');
    $this->get('/drutogoti')->assertOk();
    $this->get('/ai-hub')->assertOk();
    $this->get('/ai-catalog')->assertOk();
});

it('disables unauthenticated catalog and device ingestion routes', function () {
    $this->postJson('/api/ai-catalog/generate')->assertNotFound();
    $this->post('/iclock/cdata')->assertNotFound();
});

it('uses the attendance package fake without hardware', function () {
    AttendanceHub::fake();
    expect(app(Contracts\AttendanceGateway::class)->supportsPush('fake'))->toBeTrue();
});

it('validates structured AI output using the package fake', function () {
    AIHub::fake(['{"category":"office"}']);
    $result = app(Contracts\AiGateway::class)->suggest('accounting.categorize-bank-line', 'Supplier 01712345678', ['type' => 'object', 'properties' => ['category' => ['type' => 'string']]], ['category' => 'required|string']);
    expect($result)->toBe(['category' => 'office']);
});

it('rejects unrecognized AI jobs before calling a provider', function () {
    app(Contracts\AiGateway::class)->suggest('post.journal', '', [], []);
})->throws(RuntimeException::class);

it('generates a real native PDF through the adapter', function () {
    expect(app(Contracts\PdfRenderer::class)->render('<h1>Accounting</h1>', 'universal'))->toStartWith('%PDF-');
});

it('has unique route method and uri combinations', function () {
    $keys = [];
    foreach (app('router')->getRoutes() as $route) {
        foreach ($route->methods() as $method) {
            $key = $method.' '.$route->getDomain().' '.$route->uri();
            expect($keys)->not->toContain($key);
            $keys[] = $key;
        }
    }
});

it('keeps native scripts in master-data slugs', function (string $title) {
    expect(UniversalSlug::generate($title))->toMatch('/[^\x00-\x7F]/');
})->with(['বাংলা হিসাব', 'مرحبا بالعالم', '中文目录']);

it('uses the package PDF fake through the production adapter', function () {
    $fake = UnicodePdf::fake();
    app(Contracts\PdfRenderer::class)->render('<p>Receipt</p>');
    $fake->assertGenerated();
});

it('never seeds a default login account', function () {
    $this->seed();
    expect(User::query()->count())->toBe(0);
    expect(Permission::query()->count())->toBe(2);
});
