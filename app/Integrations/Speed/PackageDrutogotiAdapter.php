<?php

declare(strict_types=1);

namespace App\Integrations\Speed;

use App\Integrations\Contracts\DrutogotiAdapter;
use Illuminate\Support\Facades\DB;

final class PackageDrutogotiAdapter implements DrutogotiAdapter
{
    public function summary(): array
    {
        return [
            'enabled' => (bool) config('accounting.profiler.enabled'),
            'requests' => DB::table('drutogoti_requests')->count(),
            'slow_queries' => DB::table('drutogoti_query_logs')->where('duration_ms', '>=', config('drutogoti.watchers.queries.slow_threshold_ms'))->count(),
            'open_suggestions' => DB::table('drutogoti_suggestions')->where('is_resolved', false)->where('is_dismissed', false)->count(),
        ];
    }
}
