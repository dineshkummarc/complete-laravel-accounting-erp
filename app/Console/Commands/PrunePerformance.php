<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class PrunePerformance extends Command
{
    protected $signature = 'performance:prune {--days=7}';

    protected $description = 'Prune expired profiler telemetry in bounded chunks';

    public function handle(): int
    {
        $days = filter_var($this->option('days'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 3650]]);
        if ($days === false) {
            $this->error('Retention must be between 1 and 3650 days.');

            return self::FAILURE;
        }
        $cutoff = now()->subDays($days);
        $deleted = 0;
        foreach (['drutogoti_query_logs', 'drutogoti_cache_events', 'drutogoti_cookie_events', 'drutogoti_image_scans', 'drutogoti_suggestions', 'drutogoti_requests'] as $table) {
            do {
                $ids = DB::table($table)->where('created_at', '<', $cutoff)->orderBy('id')->limit(1000)->pluck('id');
                $deleted += DB::table($table)->whereIn('id', $ids)->delete();
            } while ($ids->count() === 1000);
        }
        DB::table('performance_suggestion_keys')->whereNull('suggestion_id')->delete();
        $this->info($deleted.' expired telemetry records pruned.');

        return self::SUCCESS;
    }
}
