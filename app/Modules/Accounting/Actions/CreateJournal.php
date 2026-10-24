<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Actions;

use App\Models\User;
use App\Modules\Accounting\DTOs\JournalData;
use App\Modules\Accounting\DTOs\JournalLineData;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Models\JournalLine;
use App\Modules\Accounting\Services\AccountMappings;
use App\Modules\Accounting\Services\LedgerAccess;
use App\Modules\Accounting\Services\LedgerWriteGuard;
use App\Modules\Accounting\Services\PeriodGuard;
use App\Modules\Accounting\ValueObjects\Amount;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Services\AuditTrail;
use App\Modules\Core\Services\BusinessContext;
use Brick\Math\BigDecimal;
use Brick\Money\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class CreateJournal
{
    public function handle(User $actor, JournalData $data, bool $overrideLock = false, string $reason = ''): JournalEntry
    {
        app(LedgerAccess::class)->authorize($actor, 'accounting.journal.create');
        Validator::make(['date' => $data->entryDate, 'currency' => $data->currency, 'rate' => $data->exchangeRate, 'narration' => $data->narration, 'source_type' => $data->sourceType, 'source_id' => $data->sourceId, 'version' => $data->version, 'lines' => $data->lines],
            ['date' => 'required|date_format:Y-m-d', 'currency' => 'required|string|size:3', 'rate' => 'required|regex:/^\d{1,10}(?:\.\d{1,10})?$/D', 'narration' => 'required|string|max:10000', 'source_type' => 'required|string|max:100', 'source_id' => 'required|string|max:100', 'version' => 'required|integer|min:1', 'lines' => 'required|array|min:2|max:500'])->validate();
        Currency::of($data->currency);
        $rate = BigDecimal::of($data->exchangeRate)->toScale(10);
        if (! $rate->isPositive()) {
            throw ValidationException::withMessages(['exchange_rate' => __('accounting.positive_rate')]);
        }
        $context = app(BusinessContext::class);
        $company = Company::query()->findOrFail($context->companyId);
        if ($data->currency === $company->currency && ! $rate->isEqualTo('1')) {
            throw ValidationException::withMessages(['exchange_rate' => __('accounting.base_rate_one')]);
        }
        $hash = $data->fingerprint();

        return DB::transaction(function () use ($actor, $data, $context, $rate, $hash, $overrideLock, $reason): JournalEntry {
            DB::table('companies')->where('id', $context->companyId)->lockForUpdate()->firstOrFail();
            $sourceBranch = DB::table('journal_entries')->where('company_id', $context->companyId)->where('source_type', $data->sourceType)->where('source_id', $data->sourceId)->where('source_version', $data->version)->value('branch_id');
            if ($sourceBranch !== null && $sourceBranch !== $context->branchId) {
                throw ValidationException::withMessages(['source' => __('accounting.source_changed')]);
            }
            $existing = JournalEntry::query()->where('source_type', $data->sourceType)->where('source_id', $data->sourceId)->where('source_version', $data->version)->first();
            if ($existing !== null) {
                if ($existing->source_hash !== $hash) {
                    throw ValidationException::withMessages(['source' => __('accounting.source_changed')]);
                }

                return $existing;
            }
            $period = app(PeriodGuard::class)->handle($actor, $data->entryDate, $overrideLock, $reason);
            $ids = array_unique(array_map(fn (JournalLineData $line): int => $line->accountId, $data->lines));
            if (Account::query()->whereIn('id', $ids)->where('is_active', true)->where('is_group', false)->count() !== count($ids)) {
                throw ValidationException::withMessages(['lines' => __('accounting.invalid_accounts')]);
            }

            return app(LedgerWriteGuard::class)->run(function () use ($actor, $data, $rate, $hash, $period, $context): JournalEntry {
                $entry = new JournalEntry(['entry_date' => $data->entryDate, 'fiscal_period_id' => $period->id, 'currency' => $data->currency, 'exchange_rate' => (string) $rate, 'narration' => $data->narration,
                    'source_type' => $data->sourceType, 'source_id' => $data->sourceId, 'source_version' => $data->version, 'source_hash' => $hash, 'status' => 'draft']);
                $entry->created_by = $actor->id;
                $entry->save();
                foreach ($data->lines as $dataLine) {
                    $line = new JournalLine(['journal_entry_id' => $entry->id, 'account_id' => $dataLine->accountId, 'entry_date' => $data->entryDate, 'debit' => (string) (new Amount($dataLine->debit))->value, 'credit' => (string) (new Amount($dataLine->credit))->value,
                        'base_debit' => (new Amount($dataLine->debit))->base((string) $rate), 'base_credit' => (new Amount($dataLine->credit))->base((string) $rate), 'contact_id' => $dataLine->contactId, 'cost_center' => $dataLine->costCenter, 'narration' => $dataLine->narration]);
                    $line->created_by = $actor->id;
                    $line->save();
                }
                $docNet = BigDecimal::zero();
                $baseNet = BigDecimal::zero();
                foreach ($entry->lines()->get() as $line) {
                    $docNet = $docNet->plus($line->debit)->minus($line->credit);
                    $baseNet = $baseNet->plus($line->base_debit)->minus($line->base_credit);
                }
                // Only conversion rounding may be corrected; an unbalanced document stays invalid.
                if ($docNet->isZero() && ! $baseNet->isZero()) {
                    $bound = BigDecimal::of('0.00005')->multipliedBy(count($data->lines));
                    if ($baseNet->abs()->isGreaterThan($bound)) {
                        throw ValidationException::withMessages(['lines' => __('accounting.unbalanced')]);
                    }
                    $rounding = app(AccountMappings::class)->get('rounding');
                    $entry->lines()->create(['account_id' => $rounding->id, 'entry_date' => $data->entryDate, 'debit' => '0.0000', 'credit' => '0.0000',
                        'base_debit' => $baseNet->isNegative() ? (string) $baseNet->abs()->toScale(4) : '0.0000',
                        'base_credit' => $baseNet->isPositive() ? (string) $baseNet->toScale(4) : '0.0000', 'is_rounding' => true, 'narration' => 'Currency conversion rounding']);
                }
                app(AuditTrail::class)->record($context->companyId, $context->branchId, $actor->id, 'journal.created', JournalEntry::class, (string) $entry->id, [], ['source_type' => $data->sourceType, 'source_id' => $data->sourceId]);

                return $entry->load('lines');
            });
        }, 3);
    }
}
