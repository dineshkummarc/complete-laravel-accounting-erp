<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_suggestion_keys', function (Blueprint $t): void {
            $t->id();
            $t->string('fingerprint', 64)->unique();
            $t->foreignId('suggestion_id')->nullable()->constrained('drutogoti_suggestions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_suggestion_keys');
    }
};
