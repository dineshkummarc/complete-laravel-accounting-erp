<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\FiscalPeriod;
use App\Modules\Core\Services\BusinessContext;
use Brick\Math\BigDecimal;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class BalanceReader
{
    /** @param list<int> $branches
     * @return array<int,array<int,array{debit:BigDecimal,credit:BigDecimal}>>
     */
    public function read(?string $from, string $to, array $branches, bool $excludeClosing = false): array
    {
        $company = app(BusinessContext::class)->companyId;
        $periods = FiscalPeriod::query()->where('ends_on', '<=', $to)->when($from !== null, fn ($q) => $q->where('starts_on', '>=', $from))->pluck('id')->all();
        $balances = [];
        if ($periods !== []) {
            foreach (DB::table('account_balances')->where('company_id', $company)->whereIn('branch_id', $branches)->whereIn('fiscal_period_id', $periods)->cursor() as $row) {
                $this->add($balances, $row->branch_id, $row->account_id, $row->debit, $row->credit);
            }
        }
        $partial = $this->lines($from, $to, $branches)->whereNotIn('journal_entries.fiscal_period_id', $periods);
        if ($excludeClosing) {
            $partial->where('journal_entries.source_type', '!=', 'year-end-close');
        }
        foreach ($partial->select('journal_lines.branch_id', 'journal_lines.account_id', 'journal_lines.base_debit', 'journal_lines.base_credit')->cursor() as $line) {
            $this->add($balances, $line->branch_id, $line->account_id, $line->base_debit, $line->base_credit);
        }
        if ($excludeClosing && $periods !== []) {
            foreach ($this->lines($from, $to, $branches)->whereIn('journal_entries.fiscal_period_id', $periods)->where('journal_entries.source_type', 'year-end-close')->select('journal_lines.branch_id', 'journal_lines.account_id', 'journal_lines.base_debit', 'journal_lines.base_credit')->cursor() as $line) {
                $this->add($balances, $line->branch_id, $line->account_id, (string) BigDecimal::of($line->base_debit)->negated(), (string) BigDecimal::of($line->base_credit)->negated());
            }
        }

        return $balances;
    }

    /** @param list<int> $branches */
    public function lines(?string $from, string $to, array $branches): Builder
    {
        $company = app(BusinessContext::class)->companyId;

        return DB::table('journal_lines')->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')->where('journal_lines.company_id', $company)->where('journal_entries.company_id', $company)
            ->whereColumn('journal_lines.branch_id', 'journal_entries.branch_id')->whereIn('journal_lines.branch_id', $branches)->whereIn('journal_entries.status', ['posted', 'reversed'])
            ->where('journal_entries.entry_date', '<=', $to)->when($from !== null, fn ($q) => $q->where('journal_entries.entry_date', '>=', $from));
    }

    /** @param array<int,array<int,array{debit:BigDecimal,credit:BigDecimal}>> $balances */
    private function add(array &$balances, int $branch, int $account, string $debit, string $credit): void
    {
        $balances[$branch][$account] ??= ['debit' => BigDecimal::zero(), 'credit' => BigDecimal::zero()];
        $balances[$branch][$account]['debit'] = $balances[$branch][$account]['debit']->plus($debit);
        $balances[$branch][$account]['credit'] = $balances[$branch][$account]['credit']->plus($credit);
    }

    /** @param array<int,array<int,array{debit:BigDecimal,credit:BigDecimal}>> $branches
     * @return array<int,array{debit:BigDecimal,credit:BigDecimal}>
     */
    public function aggregate(array $branches): array
    {
        $rows = [];
        foreach ($branches as $accounts) {
            foreach ($accounts as $id => $movement) {
                $rows[$id] ??= ['debit' => BigDecimal::zero(), 'credit' => BigDecimal::zero()];
                $rows[$id]['debit'] = $rows[$id]['debit']->plus($movement['debit']);
                $rows[$id]['credit'] = $rows[$id]['credit']->plus($movement['credit']);
            }
        }

        return $rows;
    }
}
