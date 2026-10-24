<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', fn (Blueprint $t) => $t->boolean('is_cash_equivalent')->default(false));
    }

    public function down(): void
    {
        Schema::table('accounts', fn (Blueprint $t) => $t->dropColumn('is_cash_equivalent'));
    }
};
