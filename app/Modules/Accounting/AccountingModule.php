<?php

declare(strict_types=1);

namespace App\Modules\Accounting;

use App\Modules\Accounting\Reports\AccountStatement;
use App\Modules\Accounting\Reports\BalanceSheet;
use App\Modules\Accounting\Reports\BankBook;
use App\Modules\Accounting\Reports\BranchComparison;
use App\Modules\Accounting\Reports\CashBook;
use App\Modules\Accounting\Reports\CashFlow;
use App\Modules\Accounting\Reports\Consolidated;
use App\Modules\Accounting\Reports\DayBook;
use App\Modules\Accounting\Reports\GeneralLedger;
use App\Modules\Accounting\Reports\IncomeStatement;
use App\Modules\Accounting\Reports\JournalRegister;
use App\Modules\Accounting\Reports\TrialBalance;
use App\Modules\Core\Modules\BaseModule;

final class AccountingModule extends BaseModule
{
    public function key(): string
    {
        return 'accounting';
    }

    public function dependsOn(): array
    {
        return ['core'];
    }

    public function optionalIntegrations(): array
    {
        return [];
    }

    public function isCore(): bool
    {
        return true;
    }

    public function permissions(): array
    {
        return [...parent::permissions(), 'accounting.account.manage', 'accounting.period.manage', 'accounting.journal.create', 'accounting.journal.approve', 'accounting.journal.post', 'accounting.journal.reverse', 'accounting.override-lock', 'accounting.report.view'];
    }

    public function reportDefinitions(): array
    {
        return [TrialBalance::class, IncomeStatement::class, BalanceSheet::class, CashFlow::class, GeneralLedger::class, DayBook::class, JournalRegister::class, AccountStatement::class, CashBook::class, BankBook::class, BranchComparison::class, Consolidated::class];
    }
}
