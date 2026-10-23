<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['accounting.profiler.enabled' => true, 'accounting.profiler.test_capture' => true,
        'accounting.profiler.sample_rate' => 1, 'drutogoti.storage.driver' => 'sync']);
    Route::get('/capture-test', function () {
        DB::select("SELECT 'private@example.com' AS secret, 123456789 AS bank_number");
        cache()->get('private-cache-key');

        return response('ok');
    });
});

it('captures requests with no SQL literals or bindings and hashes cache identifiers', function () {
    $this->get('/capture-test')->assertOk();
    $row = DB::table('drutogoti_requests')->first();
    expect($row->query_count)->toBeGreaterThan(0);
    $encoded = json_encode(DB::table('drutogoti_query_logs')->get());
    expect($encoded)->not->toContain('private@example.com')->not->toContain('123456789');
    expect(DB::table('drutogoti_cache_events')->value('key'))->toBe(hash('sha256', 'private-cache-key'));
});

it('enforces zero sampling and path exclusions', function () {
    config(['accounting.profiler.sample_rate' => 0]);
    $this->get('/capture-test')->assertOk();
    expect(DB::table('drutogoti_requests')->count())->toBe(0);
    config(['accounting.profiler.sample_rate' => 1, 'accounting.profiler.exclude_paths' => ['capture-test']]);
    $this->get('/capture-test')->assertOk();
    expect(DB::table('drutogoti_requests')->count())->toBe(0);
});
