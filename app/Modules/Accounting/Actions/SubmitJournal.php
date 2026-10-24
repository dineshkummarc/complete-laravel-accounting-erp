<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Actions;

use App\Models\User;
use App\Modules\Accounting\Enums\JournalStatus;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Services\LedgerAccess;
use App\Modules\Accounting\Services\LedgerWriteGuard;
use App\Modules\Accounting\Services\PeriodGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SubmitJournal
{
    public function handle(User $actor, int $id): JournalEntry
    {
        app(LedgerAccess::class)->authorize($actor, 'accounting.journal.create');

        return DB::transaction(function () use ($actor, $id): JournalEntry {
            $entry = JournalEntry::query()->lockForUpdate()->findOrFail($id);
            if ($entry->status !== JournalStatus::Draft) {
                throw ValidationException::withMessages(['status' => __('accounting.draft_required')]);
            }
            app(PeriodGuard::class)->handle($actor, $entry->entry_date->toDateString());

            return app(LedgerWriteGuard::class)->run(function () use ($entry): JournalEntry {
                $entry->status = JournalStatus::PendingApproval;
                $entry->save();

                return $entry;
            });
        });
    }
}
