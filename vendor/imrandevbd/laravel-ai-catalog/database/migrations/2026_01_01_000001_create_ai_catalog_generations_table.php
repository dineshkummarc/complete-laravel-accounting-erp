<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_catalog_generations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('tenant_id')->nullable()->index();
            $table->nullableMorphs('product');
            $table->string('input_mode', 32);
            $table->json('input_payload')->nullable();
            $table->string('locale', 12)->default('en');
            $table->string('tone', 32)->default('professional');
            $table->string('brand_voice')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->json('payload')->nullable();
            $table->json('mapped_payload')->nullable();
            $table->json('confidence')->nullable();
            $table->decimal('overall_confidence', 5, 4)->nullable();
            $table->json('variants')->nullable();
            $table->json('flags')->nullable();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->decimal('cost_usd', 12, 6)->default(0);
            $table->decimal('latency_ms', 10, 2)->default(0);
            $table->text('error')->nullable();
            $table->string('cache_key')->nullable()->index();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_catalog_generations');
    }
};
