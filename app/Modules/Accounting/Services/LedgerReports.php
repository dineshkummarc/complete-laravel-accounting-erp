<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Services;

use App\Models\User;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Reports\ReportDefinition;
use App\Modules\Accounting\Reports\ReportResult;
use App\Modules\Core\Services\BusinessContext;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class LedgerReports
{
    /** @param array<string,mixed> $parameters */
    public function handle(User $actor, ReportDefinition $definition, array $parameters, ?int $limit = 100): ReportResult
    {
        $scope = app(ReportScope::class)->handle($actor, $definition->key(), $parameters);
        $reader = app(BalanceReader::class);
        $key = $definition->key();
        $accounts = Account::query()->withTrashed()->orderBy('code')->get()->keyBy('id');
        $movement = $reader->aggregate($reader->read($scope->from, $scope->to, $scope->branches, $key === 'income-statement'));
        $opening = $reader->aggregate($reader->read(null, CarbonImmutable::parse($scope->from)->subDay()->toDateString(), $scope->branches));
        $rows = [];
        $totals = [];
        $zero = BigDecimal::zero();
        $net = fn (array $values): BigDecimal => $values['debit']->minus($values['credit']);
        $empty = ['debit' => $zero, 'credit' => $zero];
        if ($key === 'trial-balance') {
            foreach ($accounts as $id => $account) {
                $m = $movement[$id] ?? $empty;
                $o = $scope->includeOpening ? $net($opening[$id] ?? $empty) : $zero;
                $c = $o->plus($net($m));
                if ($o->isZero() && $m['debit']->isZero() && $m['credit']->isZero()) {
                    continue;
                }
                $row = ['code' => $account->code, 'account' => $account->name, 'opening_debit' => $this->positive($o), 'opening_credit' => $this->positive($o->negated()), 'debit' => (string) $m['debit']->toScale(4), 'credit' => (string) $m['credit']->toScale(4), 'closing_debit' => $this->positive($c), 'closing_credit' => $this->positive($c->negated())];
                foreach (['opening_debit', 'opening_credit', 'debit', 'credit', 'closing_debit', 'closing_credit'] as $column) {
                    $totals[$column] = (string) BigDecimal::of($totals[$column] ?? '0')->plus($row[$column])->toScale(4);
                }
                $rows[] = $row;
            }
        } elseif (in_array($key, ['income-statement', 'balance-sheet', 'consolidated'], true)) {
            $values = $key === 'income-statement' ? $movement : $reader->aggregate($reader->read(null, $scope->to, $scope->branches));
            $earnings = $zero;
            foreach ($accounts as $id => $account) {
                $type = $account->type->value;
                $value = $net($values[$id] ?? $empty);
                if (in_array($type, ['income', 'expense'], true)) {
                    $earnings = $earnings->minus($value);
                }
                if (($key === 'income-statement') !== in_array($type, ['income', 'expense'], true) || $value->isZero()) {
                    continue;
                }
                if (in_array($type, ['income', 'liability', 'equity'], true)) {
                    $value = $value->negated();
                }
                $rows[] = ['section' => $type, 'code' => $account->code, 'account' => $account->name, 'balance' => (string) $value->toScale(4)];
                $totals[$type] = (string) BigDecimal::of($totals[$type] ?? '0')->plus($value)->toScale(4);
            }
            if ($key === 'income-statement') {
                $totals['profit'] = (string) $earnings->toScale(4);
            } else {
                $rows[] = ['section' => 'equity', 'code' => '', 'account' => 'Unclosed earnings', 'balance' => (string) $earnings->toScale(4)];
                $totals['equity'] = (string) BigDecimal::of($totals['equity'] ?? '0')->plus($earnings)->toScale(4);
            }
        } elseif ($key === 'branch-comparison') {
            $branches = DB::table('branches')->whereIn('id', $scope->branches)->pluck('name', 'id');
            foreach ($reader->read($scope->from, $scope->to, $scope->branches, true) as $branch => $balances) {
                $revenue = $zero;
                $expense = $zero;
                foreach ($balances as $id => $value) {
                    $type = $accounts->get($id)?->type->value;
                    if ($type === 'income') {
                        $revenue = $revenue->minus($net($value));
                    }
                    if ($type === 'expense') {
                        $expense = $expense->plus($net($value));
                    }
                }
                $rows[] = ['branch' => (string) $branches->get($branch), 'revenue' => (string) $revenue->toScale(4), 'expense' => (string) $expense->toScale(4), 'profit' => (string) $revenue->minus($expense)->toScale(4)];
            }
        } elseif ($key === 'cash-flow') {
            $cashIds = $accounts->filter(fn (Account $a): bool => $a->is_cash_equivalent)->keys()->all();
            $start = $zero;
            $change = $zero;
            foreach ($cashIds as $id) {
                $start = $start->plus($net($opening[$id] ?? $empty));
                $change = $change->plus($net($movement[$id] ?? $empty));
            }
            $rows = [['section' => 'Opening cash', 'amount' => (string) $start->toScale(4)], ['section' => 'Net cash movement', 'amount' => (string) $change->toScale(4)], ['section' => 'Closing cash', 'amount' => (string) $start->plus($change)->toScale(4)]];
        } else {
            $query = $reader->lines($scope->from, $scope->to, $scope->branches)->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')->join('branches', 'branches.id', '=', 'journal_lines.branch_id');
            if ($scope->accountId !== null) {
                $query->where('journal_lines.account_id', $scope->accountId);
            }
            if (in_array($key, ['cash-book', 'bank-book'], true)) {

                $mapping = $key === 'cash-book' ? 'cash' : 'bank';
                $context = app(BusinessContext::class);
                $mapped = DB::table('account_mappings')->where('company_id', $context->companyId)->where('key', $mapping)->whereIn('branch_id', array_merge([0], $scope->branches))->pluck('account_id', 'branch_id')->all();
                $query->where(function ($query) use ($mapped, $scope): void {
                    foreach ($scope->branches as $branch) {
                        $account = $mapped[$branch] ?? $mapped[0] ?? 0;
                        $query->orWhere(fn ($q) => $q->where('journal_lines.branch_id', $branch)->where('journal_lines.account_id', $account));
                    }
                });

            }
            $running = [];
            $register = [];
            foreach ($query->orderBy('journal_entries.entry_date')->orderBy('journal_entries.id')->orderBy('journal_lines.id')->select('journal_entries.id as entry_id', 'journal_entries.entry_date', 'journal_entries.number', 'journal_entries.narration', 'journal_lines.account_id', 'journal_lines.base_debit', 'journal_lines.base_credit', 'accounts.name as account_name', 'branches.name as branch_name')->cursor() as $line) {
                $id = (int) $line->account_id;
                $running[$id] ??= $scope->includeOpening ? $net($opening[$id] ?? $empty) : $zero;
                $running[$id] = $running[$id]->plus($line->base_debit)->minus($line->base_credit);
                if ($key === 'journal-register') {
                    $register[$line->entry_id] ??= ['date' => $line->entry_date, 'number' => $line->number, 'narration' => $line->narration ?? '', 'debit' => '0.0000', 'credit' => '0.0000'];
                    foreach (['debit', 'credit'] as $side) {
                        $register[$line->entry_id][$side] = (string) BigDecimal::of($register[$line->entry_id][$side])->plus($line->{'base_'.$side})->toScale(4);
                    }
                } else {
                    $rows[] = ['date' => $line->entry_date, 'number' => $line->number, 'branch' => $line->branch_name, 'account' => $line->account_name, 'debit' => $line->base_debit, 'credit' => $line->base_credit, 'balance' => (string) $running[$id]->toScale(4)];
                }
            }
            if ($key === 'journal-register') {
                $rows = array_values($register);
            }
        }
        $count = count($rows);

        return new ReportResult($key, $definition->columns(), $limit === null ? $rows : array_slice($rows, 0, max(0, $limit)), $totals, ['from' => $scope->from, 'to' => $scope->to, 'branch_ids' => $scope->branches, 'row_count' => $count, 'truncated' => $limit !== null && $count > $limit, 'cash_flow_basis' => $key === 'cash-flow' ? 'Cash reconciliation; activity classification pending.' : null]);
    }

    private function positive(BigDecimal $value): string
    {
        return (string) ($value->isPositive() ? $value : BigDecimal::zero())->toScale(4);
    }
}
