<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', fn (Blueprint $t) => $t->string('source_hash', 64)->nullable());
    }

    public function down(): void
    {
        Schema::table('journal_entries', fn (Blueprint $t) => $t->dropColumn('source_hash'));
    }
};
