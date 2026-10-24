<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Policies;

use App\Models\User;
use App\Modules\Accounting\Enums\JournalStatus;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Services\LedgerAccess;
use App\Modules\Core\Services\BusinessContext;

final class JournalEntryPolicy
{
    public function viewAny(User $actor): bool
    {
        return app(LedgerAccess::class)->allows($actor, 'accounting.view');
    }

    public function view(User $actor, JournalEntry $entry): bool
    {
        return $entry->company_id === app(BusinessContext::class)->companyId && $entry->branch_id === app(BusinessContext::class)->branchId && $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return app(LedgerAccess::class)->allows($actor, 'accounting.journal.create');
    }

    public function update(User $actor, JournalEntry $entry): bool
    {
        return $this->view($actor, $entry) && $entry->status === JournalStatus::Draft && $this->create($actor);
    }

    public function post(User $actor, JournalEntry $entry): bool
    {
        return $this->view($actor, $entry) && ! in_array($entry->status, [JournalStatus::Posted, JournalStatus::Reversed], true) && app(LedgerAccess::class)->allows($actor, 'accounting.journal.post');
    }

    public function approve(User $actor, JournalEntry $entry): bool
    {
        return $this->view($actor, $entry) && $entry->status === JournalStatus::PendingApproval && $entry->created_by !== $actor->id && app(LedgerAccess::class)->allows($actor, 'accounting.journal.approve');
    }

    public function reverse(User $actor, JournalEntry $entry): bool
    {
        return $this->view($actor, $entry) && $entry->status === JournalStatus::Posted && app(LedgerAccess::class)->allows($actor, 'accounting.journal.reverse');
    }

    public function attach(User $actor, JournalEntry $entry): bool
    {
        return $this->view($actor, $entry) && $this->create($actor);
    }

    public function delete(User $actor, JournalEntry $entry): bool
    {
        return false;
    }
}
