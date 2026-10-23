<?php

declare(strict_types=1);

namespace App\Modules\Core\Actions;

use App\Models\User;
use App\Modules\Core\Services\BusinessContext;

final class SwitchBranch
{
    public function handle(User $actor, int $branchId): void
    {
        $context = app(BusinessContext::class);
        abort_unless($context->companyId !== null, 403);
        $context->select($actor, $context->companyId, $branchId);
        $actor->forceFill(['current_branch_id' => $branchId])->save();
    }
}
