<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Reports;

use App\Models\User;
use App\Modules\Accounting\Services\LedgerReports;

abstract class FinancialReportDefinition implements ReportDefinition
{
    abstract public function key(): string;

    public function title(): string
    {
        return __('reports.'.$this->key());
    }

    public function module(): string
    {
        return 'accounting';
    }

    public function parameters(): array
    {
        return ['type' => 'object', 'additionalProperties' => false, 'properties' => ['from' => ['type' => 'string', 'format' => 'date'], 'to' => ['type' => 'string', 'format' => 'date'], 'branch_ids' => ['type' => 'array', 'items' => ['type' => 'integer']], 'account_id' => ['type' => 'integer'], 'include_opening' => ['type' => 'boolean']]];
    }

    public function columns(): array
    {
        $keys = match ($this->key()) {
            'trial-balance' => ['code', 'account', 'opening_debit', 'opening_credit', 'debit', 'credit', 'closing_debit', 'closing_credit'],
            'income-statement','balance-sheet','consolidated' => ['section', 'code', 'account', 'balance'],
            'cash-flow' => ['section', 'amount'],
            'branch-comparison' => ['branch', 'revenue', 'expense', 'profit'],
            'journal-register' => ['date', 'number', 'narration', 'debit', 'credit'],
            'day-book' => ['date', 'number', 'branch', 'account', 'debit', 'credit'],
            default => ['date', 'number', 'branch', 'account', 'debit', 'credit', 'balance'],
        };

        return array_combine($keys, array_map(fn (string $key): string => __('reports.column_'.$key), $keys));
    }

    public function query(User $actor, array $parameters, ?int $limit = 100): ReportResult
    {
        return app(LedgerReports::class)->handle($actor, $this, $parameters, $limit);
    }
}
