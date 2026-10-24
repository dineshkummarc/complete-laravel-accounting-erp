<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Actions;

use App\Models\User;
use App\Modules\Accounting\Services\BalanceReader;
use App\Modules\Accounting\Services\LedgerAccess;
use App\Modules\Accounting\ValueObjects\Amount;
use App\Modules\Core\Services\AuditTrail;
use App\Modules\Core\Services\BusinessContext;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

final class RebuildBalances
{
    public function handle(User $actor): int
    {
        app(LedgerAccess::class)->authorize($actor, 'accounting.account.manage');
        $context = app(BusinessContext::class);

        return DB::transaction(function () use ($actor, $context): int {
            DB::table('companies')->where('id', $context->companyId)->lockForUpdate()->firstOrFail();
            $balances = [];
            foreach (app(BalanceReader::class)->lines(null, '9999-12-31', [$context->branchId])->select('journal_entries.fiscal_period_id', 'journal_lines.account_id', 'journal_lines.base_debit', 'journal_lines.base_credit')->cursor() as $line) {
                $key = $line->fiscal_period_id.':'.$line->account_id;
                $balances[$key] ??= ['period' => $line->fiscal_period_id, 'account' => $line->account_id, 'debit' => BigDecimal::zero(), 'credit' => BigDecimal::zero()];
                $balances[$key]['debit'] = $balances[$key]['debit']->plus($line->base_debit);
                $balances[$key]['credit'] = $balances[$key]['credit']->plus($line->base_credit);
            }
            DB::table('account_balances')->where('company_id', $context->companyId)->where('branch_id', $context->branchId)->delete();
            foreach (array_chunk($balances, 500) as $chunk) {
                $rows = [];
                foreach ($chunk as $balance) {
                    $rows[] = ['company_id' => $context->companyId, 'branch_id' => $context->branchId, 'fiscal_period_id' => $balance['period'], 'account_id' => $balance['account'], 'debit' => (string) (new Amount((string) $balance['debit']->toScale(4)))->value, 'credit' => (string) (new Amount((string) $balance['credit']->toScale(4)))->value, 'created_at' => now(), 'updated_at' => now()];
                }
                DB::table('account_balances')->insert($rows);
            }
            app(AuditTrail::class)->record($context->companyId, $context->branchId, $actor->id, 'balances.rebuilt', 'account_balances', (string) $context->branchId, [], ['rows' => count($balances)]);

            return count($balances);
        }, 3);
    }
}
