<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Actions;

use App\Models\User;
use App\Modules\Accounting\Enums\JournalStatus;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\FiscalYear;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Services\ApprovalRules;
use App\Modules\Accounting\Services\LedgerAccess;
use App\Modules\Accounting\Services\LedgerWriteGuard;
use App\Modules\Accounting\Services\PeriodGuard;
use App\Modules\Accounting\ValueObjects\Amount;
use App\Modules\Core\Services\AuditTrail;
use App\Modules\Core\Services\BusinessContext;
use App\Modules\Core\Services\NumberSequence;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PostJournal
{
    public function handle(User $actor, int $id, bool $overrideLock = false, string $reason = ''): JournalEntry
    {
        app(LedgerAccess::class)->authorize($actor, 'accounting.journal.post');

        return DB::transaction(function () use ($actor, $id, $overrideLock, $reason): JournalEntry {
            DB::table('companies')->where('id', app(BusinessContext::class)->companyId)->lockForUpdate()->firstOrFail();
            $entry = JournalEntry::query()->with('lines')->lockForUpdate()->findOrFail($id);
            if (in_array($entry->status, [JournalStatus::Posted, JournalStatus::Reversed], true)) {
                return $entry;
            }
            $period = app(PeriodGuard::class)->handle($actor, $entry->entry_date->toDateString(), $overrideLock, $reason);
            $accountIds = $entry->lines->pluck('account_id')->unique()->all();
            if (Account::query()->whereIn('id', $accountIds)->where('is_active', true)->where('is_group', false)->count() !== count($accountIds)) {
                throw ValidationException::withMessages(['lines' => __('accounting.invalid_accounts')]);
            }
            $baseDebit = BigDecimal::zero();
            $baseCredit = BigDecimal::zero();
            $debit = BigDecimal::zero();
            $credit = BigDecimal::zero();
            foreach ($entry->lines as $line) {
                $baseDebit = $baseDebit->plus($line->base_debit);
                $baseCredit = $baseCredit->plus($line->base_credit);
                $debit = $debit->plus($line->debit);
                $credit = $credit->plus($line->credit);
            }
            if ($entry->lines->count() < 2 || $baseDebit->isZero() || ! $baseDebit->isEqualTo($baseCredit) || ! $debit->isEqualTo($credit)) {
                throw ValidationException::withMessages(['lines' => __('accounting.unbalanced')]);
            }
            if (app(ApprovalRules::class)->required($entry) && ($entry->status !== JournalStatus::PendingApproval || $entry->approved_by === null || $entry->approved_by === $entry->created_by)) {
                throw ValidationException::withMessages(['approval' => __('accounting.approval_required')]);
            }

            return app(LedgerWriteGuard::class)->run(function () use ($actor, $entry, $period): JournalEntry {
                $year = FiscalYear::query()->findOrFail($period->fiscal_year_id);
                $entry->number = app(NumberSequence::class)->next('journal', $year->starts_on->year, 'JV');
                $entry->status = JournalStatus::Posted;
                $entry->posted_by = $actor->id;
                $entry->posted_at = CarbonImmutable::now('UTC');
                $entry->save();
                $movements = [];
                foreach ($entry->lines as $line) {
                    $movements[$line->account_id] ??= ['debit' => BigDecimal::zero(), 'credit' => BigDecimal::zero()];
                    $movements[$line->account_id]['debit'] = $movements[$line->account_id]['debit']->plus($line->base_debit);
                    $movements[$line->account_id]['credit'] = $movements[$line->account_id]['credit']->plus($line->base_credit);
                }
                $existing = DB::table('account_balances')->where('company_id', $entry->company_id)->where('branch_id', $entry->branch_id)->where('fiscal_period_id', $entry->fiscal_period_id)->whereIn('account_id', array_keys($movements))->get()->keyBy('account_id');
                $rows = [];
                foreach ($movements as $accountId => $movement) {
                    $balance = $existing->get($accountId);
                    $debit = BigDecimal::of($balance->debit ?? '0')->plus($movement['debit'])->toScale(4);
                    $credit = BigDecimal::of($balance->credit ?? '0')->plus($movement['credit'])->toScale(4);
                    $rows[] = ['company_id' => $entry->company_id, 'branch_id' => $entry->branch_id, 'account_id' => $accountId, 'fiscal_period_id' => $entry->fiscal_period_id,
                        'debit' => (string) (new Amount((string) $debit))->value,
                        'credit' => (string) (new Amount((string) $credit))->value, 'created_at' => now(), 'updated_at' => now()];
                }
                DB::table('account_balances')->upsert($rows, ['company_id', 'branch_id', 'account_id', 'fiscal_period_id'], ['debit', 'credit', 'updated_at']);
                if ($entry->reversal_of !== null) {
                    $original = JournalEntry::query()->lockForUpdate()->findOrFail($entry->reversal_of);
                    if ($original->status !== JournalStatus::Posted) {
                        throw ValidationException::withMessages(['reversal' => __('accounting.posted_required')]);
                    }
                    $original->status = JournalStatus::Reversed;
                    $original->reversal_entry_id = $entry->id;
                    $original->save();
                }
                app(AuditTrail::class)->record($entry->company_id, $entry->branch_id, $actor->id, 'journal.posted', JournalEntry::class, (string) $entry->id, [], ['number' => $entry->number]);

                return $entry;
            });
        }, 3);
    }
}
