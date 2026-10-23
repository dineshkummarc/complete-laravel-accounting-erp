<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class AppDoctor extends Command
{
    protected $signature = 'app:doctor {--production : Require MySQL/MariaDB, Redis, and all production extensions}';

    protected $description = 'Check foundation runtime readiness; does not certify business module completion';

    public function handle(): int
    {
        $failures = [];
        foreach (['mbstring', 'intl', 'bcmath', 'sockets', 'pdo_mysql', ...($this->option('production') ? ['gd', 'redis'] : [])] as $extension) {
            if (! extension_loaded($extension)) {
                $failures[] = 'Missing PHP extension: '.$extension;
            }
        }
        try {
            DB::select('SELECT 1');
        } catch (Throwable) {
            $failures[] = 'Database connection failed.';
        }
        if ($this->option('production')) {
            if (DB::getDriverName() !== 'mysql') {
                $failures[] = 'Production requires MySQL 8 or compatible MariaDB.';
            }
            try {
                Redis::connection()->ping();
            } catch (Throwable) {
                $failures[] = 'Redis connection failed.';
            }
        }
        foreach (['NotoSans-Regular.ttf', 'NotoSansBengali-Regular.ttf', 'NotoSansArabic-Regular.ttf'] as $font) {
            if (! is_file(storage_path('app/unicode-pdf/fonts/'.$font))) {
                $failures[] = 'Missing PDF font: '.$font;
            }
        }
        foreach ($failures as $failure) {
            $this->error($failure);
        }
        if ($failures !== []) {
            return self::FAILURE;
        }
        $this->info('Foundation checks passed. Business modules are not yet certified.');

        return self::SUCCESS;
    }
}
