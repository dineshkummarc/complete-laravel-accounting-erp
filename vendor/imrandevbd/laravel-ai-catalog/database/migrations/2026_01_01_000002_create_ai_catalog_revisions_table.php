<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_catalog_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generation_id')->constrained('ai_catalog_generations')->cascadeOnDelete();
            $table->string('actor_type', 32)->default('ai'); // ai|human
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action', 32);
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_catalog_revisions');
    }
};
