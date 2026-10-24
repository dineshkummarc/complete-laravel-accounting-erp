<?php

declare(strict_types=1);

namespace App\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Facades\DB;

final class DecimalColumn
{
    public static function add(Blueprint $table, string $name, int $precision = 20, int $scale = 4): ColumnDefinition
    {
        // SQLite NUMERIC affinity converts decimal strings to binary floats. Tests retain exact text; MySQL stores native DECIMAL.
        return DB::getDriverName() === 'sqlite' ? $table->text($name) : $table->decimal($name, $precision, $scale);
    }
}
