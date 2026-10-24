<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Actions;

use App\Models\User;
use App\Modules\Accounting\Enums\JournalStatus;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Models\JournalLine;
use App\Modules\Accounting\Services\ApprovalRules;
use App\Modules\Accounting\Services\LedgerAccess;
use App\Modules\Accounting\Services\LedgerWriteGuard;
use App\Modules\Accounting\Services\PeriodGuard;
use App\Modules\Core\Services\AuditTrail;
use App\Modules\Core\Services\BusinessContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class ReverseJournal
{
    public function handle(User $actor, int $id, string $date, string $reason): JournalEntry
    {
        app(LedgerAccess::class)->authorize($actor, 'accounting.journal.reverse');
        Validator::make(['date' => $date, 'reason' => $reason], ['date' => 'required|date_format:Y-m-d', 'reason' => 'required|string|max:10000'])->validate();

        return DB::transaction(function () use ($actor, $id, $date, $reason): JournalEntry {
            DB::table('companies')->where('id', app(BusinessContext::class)->companyId)->lockForUpdate()->firstOrFail();
            $original = JournalEntry::query()->with('lines')->lockForUpdate()->findOrFail($id);
            $existing = JournalEntry::query()->where('reversal_of', $original->id)->first();
            if ($existing !== null) {
                return $existing;
            }
            if ($original->status !== JournalStatus::Posted) {
                throw ValidationException::withMessages(['reversal' => __('accounting.posted_required')]);
            }
            $period = app(PeriodGuard::class)->handle($actor, $date);
            $reversal = app(LedgerWriteGuard::class)->run(function () use ($actor, $original, $period, $date, $reason): JournalEntry {
                $entry = new JournalEntry(['entry_date' => $date, 'fiscal_period_id' => $period->id, 'currency' => $original->currency, 'exchange_rate' => $original->exchange_rate, 'narration' => $reason,
                    'source_type' => 'reversal', 'source_id' => (string) $original->id, 'source_version' => 1, 'source_hash' => hash('sha256', $original->uuid.':'.$date.':'.$reason), 'status' => 'draft', 'reversal_of' => $original->id]);
                $entry->created_by = $actor->id;
                $entry->save();
                foreach ($original->lines as $old) {
                    $line = new JournalLine(['journal_entry_id' => $entry->id, 'account_id' => $old->account_id, 'entry_date' => $date, 'debit' => $old->credit, 'credit' => $old->debit,
                        'base_debit' => $old->base_credit, 'base_credit' => $old->base_debit, 'contact_id' => $old->contact_id, 'cost_center' => $old->cost_center, 'narration' => $reason, 'is_rounding' => $old->is_rounding]);
                    $line->created_by = $actor->id;
                    $line->save();
                }

                return $entry->load('lines');
            });
            $result = app(ApprovalRules::class)->required($reversal) ? app(SubmitJournal::class)->handle($actor, $reversal->id) : app(PostJournal::class)->handle($actor, $reversal->id);
            app(AuditTrail::class)->record($original->company_id, $original->branch_id, $actor->id, 'journal.reversal_created', JournalEntry::class, (string) $original->id, [], ['reversal_id' => $result->id, 'reason' => $reason]);

            return $result;
        }, 3);
    }
}
