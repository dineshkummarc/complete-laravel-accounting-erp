<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }
        foreach (['fiscal_years' => ['starts_on', 'ends_on'], 'fiscal_periods' => ['starts_on', 'ends_on'], 'journal_entries' => ['entry_date'], 'journal_lines' => ['entry_date'], 'exchange_rates' => ['rate_date']] as $table => $columns) {
            foreach ($columns as $column) {
                DB::table($table)->update([$column => DB::raw('substr('.$column.',1,10)')]);
            }
        }
    }

    public function down(): void {}
};
