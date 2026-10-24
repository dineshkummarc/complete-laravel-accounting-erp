<?php

declare(strict_types=1);

use App\Database\DecimalColumn as Money;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function identity(Blueprint $t, bool $branch = false): void
    {
        $t->id();
        $t->uuid('uuid')->unique();
        $t->foreignId('company_id')->constrained();
        if ($branch) {
            $t->foreignId('branch_id')->constrained();
        }
        $t->foreignId('created_by')->constrained('users');
        $t->foreignId('updated_by')->nullable()->constrained('users');
        $t->timestamps();
    }

    public function up(): void
    {
        Schema::table('companies', fn (Blueprint $t) => $t->json('accounting_settings')->nullable());
        Schema::create('accounts', function (Blueprint $t): void {
            $this->identity($t);
            $t->string('code', 30);
            $t->string('name');
            $t->string('name_bn')->nullable();
            $t->string('type', 20);
            $t->string('normal_balance', 6);
            $t->boolean('is_active')->default(true);
            $t->boolean('is_group')->default(false);
            $t->foreignId('parent_id')->nullable()->constrained('accounts');
            $t->softDeletes();
            $t->unique(['company_id', 'code']);
        });
        Schema::create('fiscal_years', function (Blueprint $t): void {
            $this->identity($t);
            $t->string('name');
            $t->date('starts_on');
            $t->date('ends_on');
            $t->unique(['company_id', 'starts_on']);
        });
        Schema::create('fiscal_periods', function (Blueprint $t): void {
            $this->identity($t);
            $t->foreignId('fiscal_year_id')->constrained();
            $t->string('name');
            $t->date('starts_on');
            $t->date('ends_on');
            $t->boolean('is_locked')->default(false);
            $t->foreignId('locked_by')->nullable()->constrained('users');
            $t->timestamp('locked_at')->nullable();
            $t->unique(['company_id', 'starts_on']);
            $t->index(['company_id', 'starts_on', 'ends_on']);
        });
        Schema::create('journal_entries', function (Blueprint $t): void {
            $this->identity($t, true);
            $t->string('number')->nullable();
            $t->date('entry_date');
            $t->foreignId('fiscal_period_id')->constrained();
            $t->string('status', 30)->default('draft');
            $t->string('currency', 3);
            Money::add($t, 'exchange_rate', 20, 10);
            $t->text('narration');
            $t->string('source_type', 100);
            $t->string('source_id', 100);
            $t->unsignedInteger('source_version')->default(1);
            $t->foreignId('approved_by')->nullable()->constrained('users');
            $t->foreignId('posted_by')->nullable()->constrained('users');
            $t->timestamp('posted_at')->nullable();
            $t->foreignId('reversal_of')->nullable()->unique()->constrained('journal_entries');
            $t->foreignId('reversal_entry_id')->nullable()->constrained('journal_entries');
            $t->unique(['company_id', 'source_type', 'source_id', 'source_version'], 'journal_source_version');
            $t->unique(['company_id', 'branch_id', 'number']);
            $t->index(['company_id', 'branch_id', 'entry_date', 'status']);
        });
        Schema::create('journal_lines', function (Blueprint $t): void {
            $this->identity($t, true);
            $t->foreignId('journal_entry_id')->constrained();
            $t->foreignId('account_id')->constrained();
            $t->date('entry_date');
            foreach (['debit', 'credit', 'base_debit', 'base_credit'] as $column) {
                Money::add($t, $column)->default('0.0000');
            }
            $t->unsignedBigInteger('contact_id')->nullable();
            $t->string('cost_center')->nullable();
            $t->text('narration')->nullable();
            $t->boolean('is_rounding')->default(false);
            $t->index(['company_id', 'account_id', 'branch_id', 'entry_date'], 'journal_line_reporting');
        });
        Schema::create('account_balances', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('company_id')->constrained();
            $t->foreignId('branch_id')->constrained();
            $t->foreignId('account_id')->constrained();
            $t->foreignId('fiscal_period_id')->constrained();
            Money::add($t, 'debit')->default('0.0000');
            Money::add($t, 'credit')->default('0.0000');
            $t->timestamps();
            $t->unique(['company_id', 'branch_id', 'account_id', 'fiscal_period_id'], 'account_balance_scope');
        });
        Schema::create('account_mappings', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('company_id')->constrained();
            $t->unsignedBigInteger('branch_id')->default(0);
            $t->string('key', 60);
            $t->foreignId('account_id')->constrained();
            $t->timestamps();
            $t->unique(['company_id', 'branch_id', 'key']);
        });
        Schema::create('exchange_rates', function (Blueprint $t): void {
            $this->identity($t);
            $t->date('rate_date');
            $t->string('currency', 3);
            Money::add($t, 'rate', 20, 10);
            $t->unique(['company_id', 'rate_date', 'currency']);
        });
        Schema::create('tax_rates', function (Blueprint $t): void {
            $this->identity($t);
            $t->string('name');
            Money::add($t, 'rate', 20, 10);
            $t->boolean('is_inclusive')->default(false);
            $t->boolean('is_compound')->default(false);
            $t->foreignId('input_account_id')->nullable()->constrained('accounts');
            $t->foreignId('output_account_id')->nullable()->constrained('accounts');
            $t->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        foreach (['tax_rates', 'exchange_rates', 'account_mappings', 'account_balances', 'journal_lines', 'journal_entries', 'fiscal_periods', 'fiscal_years', 'accounts'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('companies', fn (Blueprint $t) => $t->dropColumn('accounting_settings'));
    }
};
