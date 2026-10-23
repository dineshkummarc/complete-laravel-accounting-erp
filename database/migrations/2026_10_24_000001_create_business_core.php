<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $t): void {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('currency', 3)->default('BDT');
            $t->string('locale', 5)->default('en');
            $t->string('localization_pack')->default('generic');
            $t->string('timezone')->default('UTC');
            $t->unsignedTinyInteger('fiscal_year_start_month')->default(1);
            $t->foreignId('created_by')->constrained('users');
            $t->foreignId('updated_by')->nullable()->constrained('users');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('company_user', function (Blueprint $t): void {
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->boolean('is_owner')->default(false);
            $t->timestamps();
            $t->primary(['company_id', 'user_id']);
        });
        Schema::create('branches', function (Blueprint $t): void {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('company_id')->constrained();
            $t->string('name');
            $t->string('code', 20);
            $t->string('slug');
            $t->string('timezone');
            $t->boolean('is_active')->default(true);
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->unsignedInteger('geofence_radius')->nullable();
            $t->foreignId('created_by')->constrained('users');
            $t->foreignId('updated_by')->nullable()->constrained('users');
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['company_id', 'code']);
            $t->unique(['company_id', 'slug']);
        });
        Schema::create('branch_user', function (Blueprint $t): void {
            $t->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->primary(['branch_id', 'user_id']);
        });
        Schema::table('users', function (Blueprint $t): void {
            $t->foreignId('current_company_id')->nullable()->constrained('companies');
            $t->foreignId('current_branch_id')->nullable()->constrained('branches');
            $t->string('locale', 5)->default('en');
        });
        Schema::create('company_modules', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('company_id')->constrained();
            $t->string('module_key', 60);
            $t->boolean('enabled');
            $t->json('settings')->nullable();
            $t->timestamp('enabled_at')->nullable();
            $t->foreignId('enabled_by')->nullable()->constrained('users');
            $t->timestamp('disabled_at')->nullable();
            $t->foreignId('disabled_by')->nullable()->constrained('users');
            $t->timestamps();
            $t->unique(['company_id', 'module_key']);
        });
        Schema::create('branch_modules', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('branch_id')->constrained();
            $t->string('module_key', 60);
            $t->boolean('enabled')->default(false);
            $t->timestamps();
            $t->unique(['branch_id', 'module_key']);
        });
        Schema::create('module_events', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('company_id')->constrained();
            $t->foreignId('branch_id')->nullable()->constrained();
            $t->string('module_key', 60);
            $t->string('action');
            $t->text('reason');
            $t->foreignId('actor_id')->constrained('users');
            $t->timestamps();
        });
        Schema::create('audit_events', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('company_id')->constrained();
            $t->foreignId('branch_id')->nullable()->constrained();
            $t->foreignId('actor_id')->nullable()->constrained('users');
            $t->string('action');
            $t->string('subject_type');
            $t->string('subject_id');
            $t->json('before')->nullable();
            $t->json('after')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->string('previous_hash', 64)->nullable();
            $t->string('hash', 64);
            $t->timestamps();
            $t->index(['company_id', 'id']);
        });
        Schema::create('number_sequences', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('company_id')->constrained();
            $t->foreignId('branch_id')->constrained();
            $t->string('document_type', 50);
            $t->unsignedSmallInteger('fiscal_year');
            $t->unsignedBigInteger('last_number')->default(0);
            $t->timestamps();
            $t->unique(['company_id', 'branch_id', 'document_type', 'fiscal_year'], 'sequence_scope');
        });
    }

    public function down(): void
    {
        foreach (['number_sequences', 'audit_events', 'module_events', 'branch_modules', 'company_modules'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $t): void {
            $t->dropConstrainedForeignId('current_branch_id');
            $t->dropConstrainedForeignId('current_company_id');
            $t->dropColumn('locale');
        });
        foreach (['branch_user', 'branches', 'company_user', 'companies'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
