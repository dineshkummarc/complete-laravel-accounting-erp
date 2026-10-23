<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['app.access', 'platform.console'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
    }
}
