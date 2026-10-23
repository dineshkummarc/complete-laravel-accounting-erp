<?php

declare(strict_types=1);

namespace App\Modules\Core\Policies;

use App\Models\User;
use App\Modules\Core\Models\ImportBatch;
use App\Modules\Core\Services\BusinessContext;

final class ImportBatchPolicy
{
    public function view(User $user, ImportBatch $batch): bool
    {
        return $batch->company_id === app(BusinessContext::class)->companyId && $batch->created_by === $user->id && app(BusinessContext::class)->canAccessBranch($user, $batch->company_id, $batch->branch_id);
    }

    public function update(User $user, ImportBatch $batch): bool
    {
        return false;
    }

    public function delete(User $user, ImportBatch $batch): bool
    {
        return false;
    }
}
