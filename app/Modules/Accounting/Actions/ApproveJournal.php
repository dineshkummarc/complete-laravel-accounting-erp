<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Actions;

use App\Models\User;
use App\Modules\Accounting\Enums\JournalStatus;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Services\LedgerAccess;
use App\Modules\Accounting\Services\LedgerWriteGuard;
use App\Modules\Core\Services\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ApproveJournal
{
    public function handle(User $actor, int $id): JournalEntry
    {
        app(LedgerAccess::class)->authorize($actor, 'accounting.journal.approve');

        return DB::transaction(function () use ($actor, $id): JournalEntry {
            $entry = JournalEntry::query()->lockForUpdate()->findOrFail($id);
            if ($entry->status !== JournalStatus::PendingApproval || $entry->created_by === $actor->id) {
                throw ValidationException::withMessages(['approval' => __('accounting.independent_approval')]);
            }

            return app(LedgerWriteGuard::class)->run(function () use ($actor, $entry): JournalEntry {
                $entry->approved_by = $actor->id;
                $entry->save();
                app(AuditTrail::class)->record($entry->company_id, $entry->branch_id, $actor->id, 'journal.approved', JournalEntry::class, (string) $entry->id);

                return $entry;
            });
        });
    }
}
