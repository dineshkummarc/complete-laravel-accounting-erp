<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_rates', fn (Blueprint $t) => $t->boolean('is_withholding')->default(false));
        Schema::create('tax_groups', function (Blueprint $t): void {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('company_id')->constrained();
            $t->string('name');
            $t->boolean('is_active')->default(true);
            $t->foreignId('created_by')->nullable()->constrained('users');
            $t->foreignId('updated_by')->nullable()->constrained('users');
            $t->timestamps();
            $t->unique(['company_id', 'name']);
        });
        Schema::create('tax_group_rate', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('tax_group_id')->constrained()->cascadeOnDelete();
            $t->foreignId('tax_rate_id')->constrained();
            $t->unsignedSmallInteger('position');
            $t->unique(['tax_group_id', 'tax_rate_id']);
            $t->unique(['tax_group_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_group_rate');
        Schema::dropIfExists('tax_groups');
        Schema::table('tax_rates', fn (Blueprint $t) => $t->dropColumn('is_withholding'));
    }
};
