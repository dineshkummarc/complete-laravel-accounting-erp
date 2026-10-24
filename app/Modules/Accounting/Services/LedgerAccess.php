<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Services;

use App\Models\User;
use App\Modules\Core\Modules\ModuleManager;
use App\Modules\Core\Services\BusinessContext;
use App\Modules\Core\Services\CompanyAccess;
use Illuminate\Auth\Access\AuthorizationException;

final class LedgerAccess
{
    public function allows(User $actor, string $permission, bool $requireBranch = true): bool
    {
        $context = app(BusinessContext::class);
        if ($context->companyId === null || ! app(CompanyAccess::class)->isMember($actor, $context->companyId) || ! app(ModuleManager::class)->isEnabled('accounting')) {
            return false;
        }
        if ($requireBranch && ($context->branchId === null || ! $context->canAccessBranch($actor, $context->companyId, $context->branchId))) {
            return false;
        }
        $owner = app(CompanyAccess::class)->isOwner($actor, $context->companyId);

        return $owner || $actor->can($permission);
    }

    public function authorize(User $actor, string $permission, bool $requireBranch = true): void
    {
        if (! $this->allows($actor, $permission, $requireBranch)) {
            throw new AuthorizationException('Accounting permission and branch access required.');
        }
        app(BusinessContext::class)->actorId = $actor->id;
    }
}
