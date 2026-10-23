<?php

declare(strict_types=1);

namespace App\Integrations\Speed;

use Drutogoti\Jobs\StoreCapturedEvents;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class RequestCapture
{
    private bool $active = false;

    private float $started = 0;

    /** @var list<array<string, mixed>> */
    private array $queries = [];

    /** @var list<array<string, mixed>> */
    private array $cacheEvents = [];

    private string $id = '';

    public function begin(Request $request): void
    {
        $this->queries = [];
        $this->cacheEvents = [];
        $this->active = false;
        if (! config('accounting.profiler.enabled') || app()->environment('testing') && ! config('accounting.profiler.test_capture')) {
            return;
        }
        foreach ((array) config('accounting.profiler.exclude_paths', []) as $path) {
            if ($request->is($path)) {
                return;
            }
        }
        if (random_int(1, 10000) > (int) ((float) config('accounting.profiler.sample_rate') * 10000)) {
            return;
        }
        $this->active = true;
        $this->started = microtime(true);
        $this->id = (string) Str::uuid();
    }

    public function query(QueryExecuted $query): void
    {
        if (! $this->active || str_contains($query->sql, 'drutogoti_')) {
            return;
        }
        // Never persist bindings or literal values, including statements issued through raw SQL.
        $fingerprint = preg_replace(["/'(?:''|[^'])*'/s", '/\\b\\d+(?:\\.\\d+)?\\b/'], '?', $query->sql) ?? '';
        $sql = preg_replace(["/'(?:''|[^'])*'/s", '/"(?:""|[^"])*"/s', '/\b\d+(?:\.\d+)?\b/'], '?', $query->sql) ?? '[redacted]';
        $this->queries[] = ['request_id' => $this->id, 'sql' => $sql.' /* '.hash('sha256', $fingerprint).' */', 'bindings' => [], 'duration_ms' => $query->time,
            'connection' => $query->connectionName, 'route' => null, 'controller_action' => null, 'created_at' => now(), 'updated_at' => now()];
    }

    public function cache(string $event, string $key, ?string $store): void
    {
        if ($this->active && count($this->cacheEvents) < 1000) {
            $this->cacheEvents[] = ['request_id' => $this->id, 'store' => $store ?? 'default',
                'key' => hash('sha256', $key), 'event' => $event, 'created_at' => now(), 'updated_at' => now()];
        }
    }

    public function finish(Request $request, Response $response): void
    {
        if (! $this->active) {
            return;
        }
        $this->active = false;
        $payload = ['request' => ['request_id' => $this->id, 'method' => $request->method(),
            'path' => $request->route()?->uri() ?? '[unmatched]', 'status_code' => $response->getStatusCode(),
            'total_duration_ms' => (microtime(true) - $this->started) * 1000,
            'memory_peak_mb' => memory_get_peak_usage(true) / 1048576, 'query_count' => count($this->queries),
            'cache_hit_count' => count(array_filter($this->cacheEvents, fn (array $row): bool => $row['event'] === 'hit')),
            'cache_miss_count' => count(array_filter($this->cacheEvents, fn (array $row): bool => $row['event'] === 'miss'))], 'queries' => $this->queries, 'cache_events' => $this->cacheEvents, 'cookie_events' => []];
        $this->queries = [];
        $job = new StoreCapturedEvents($payload);
        if (config('drutogoti.storage.driver') === 'sync') {
            dispatch_sync($job);
        } else {
            dispatch($job)->onConnection(config('drutogoti.storage.queue_connection'))->onQueue(config('drutogoti.storage.queue_name'));
        }
    }
}
