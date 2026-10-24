<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Policies;

use App\Models\User;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Services\LedgerAccess;
use App\Modules\Core\Services\BusinessContext;

final class AccountPolicy
{
    public function viewAny(User $actor): bool
    {
        return app(LedgerAccess::class)->allows($actor, 'accounting.view', false);
    }

    public function view(User $actor, Account $account): bool
    {
        return $account->company_id === app(BusinessContext::class)->companyId && $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return app(LedgerAccess::class)->allows($actor, 'accounting.account.manage', false);
    }

    public function update(User $actor, Account $account): bool
    {
        return $this->view($actor, $account) && $this->create($actor);
    }

    public function delete(User $actor, Account $account): bool
    {
        return false;
    }
}
