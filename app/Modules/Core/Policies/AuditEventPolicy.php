<?php

declare(strict_types=1);

namespace App\Modules\Core\Policies;

use App\Models\User;
use App\Modules\Core\Models\AuditEvent;
use App\Modules\Core\Services\BusinessContext;
use Illuminate\Support\Facades\DB;

final class AuditEventPolicy
{
    public function view(User $user, AuditEvent $event): bool
    {
        return $event->company_id === app(BusinessContext::class)->companyId && DB::table('company_user')->where('company_id', $event->company_id)->where('user_id', $user->id)->where('is_owner', true)->exists();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AuditEvent $event): bool
    {
        return false;
    }

    public function delete(User $user, AuditEvent $event): bool
    {
        return false;
    }
}
