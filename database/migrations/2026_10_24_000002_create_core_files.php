<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $t): void {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('company_id')->constrained();
            $t->foreignId('branch_id')->constrained();
            $t->string('subject_type');
            $t->unsignedBigInteger('subject_id');
            $t->string('disk');
            $t->string('path');
            $t->string('original_name');
            $t->string('mime_type');
            $t->unsignedBigInteger('size');
            $t->string('sha256', 64);
            $t->foreignId('created_by')->constrained('users');
            $t->foreignId('updated_by')->nullable()->constrained('users');
            $t->timestamps();
            $t->index(['company_id', 'branch_id', 'subject_type', 'subject_id'], 'attachment_subject');
        });
        Schema::create('import_batches', function (Blueprint $t): void {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('company_id')->constrained();
            $t->foreignId('branch_id')->constrained();
            $t->string('definition');
            $t->string('path');
            $t->string('status')->default('queued');
            $t->unsignedInteger('processed')->default(0);
            $t->unsignedInteger('failed')->default(0);
            $t->json('errors')->nullable();
            $t->foreignId('created_by')->constrained('users');
            $t->foreignId('updated_by')->nullable()->constrained('users');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
        Schema::dropIfExists('attachments');
    }
};
