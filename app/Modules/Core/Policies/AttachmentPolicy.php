<?php

declare(strict_types=1);

namespace App\Modules\Core\Policies;

use App\Models\User;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Core\Models\Attachment;
use App\Modules\Core\Services\BusinessContext;

final class AttachmentPolicy
{
    public function view(User $user, Attachment $attachment): bool
    {
        if ($attachment->subject_type === JournalEntry::class) {
            $entry = JournalEntry::query()->find($attachment->subject_id);
            if ($entry === null || ! $user->can('view', $entry)) {
                return false;
            }
        }

        return $attachment->company_id === app(BusinessContext::class)->companyId && app(BusinessContext::class)->canAccessBranch($user, $attachment->company_id, $attachment->branch_id);
    }

    public function update(User $user, Attachment $attachment): bool
    {
        return false;
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        return false;
    }
}
