<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Actions;

use App\Models\User;
use App\Modules\Accounting\Models\FiscalPeriod;
use App\Modules\Accounting\Services\LedgerAccess;
use App\Modules\Core\Services\AuditTrail;
use App\Modules\Core\Services\BusinessContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LockPeriod
{
    public function handle(User $actor, int $id, bool $locked, string $reason): void
    {
        app(LedgerAccess::class)->authorize($actor, 'accounting.period.manage', false);
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => __('accounting.reason_required')]);
        }
        DB::transaction(function () use ($actor, $id, $locked, $reason): void {
            DB::table('companies')->where('id', app(BusinessContext::class)->companyId)->lockForUpdate()->firstOrFail();
            $period = FiscalPeriod::query()->lockForUpdate()->findOrFail($id);
            $period->fill(['is_locked' => $locked, 'locked_by' => $locked ? $actor->id : null, 'locked_at' => $locked ? now() : null]);
            $period->save();
            app(AuditTrail::class)->record($period->company_id, null, $actor->id, $locked ? 'period.locked' : 'period.unlocked', FiscalPeriod::class, (string) $period->id, [], ['reason' => $reason]);
        });
    }
}
