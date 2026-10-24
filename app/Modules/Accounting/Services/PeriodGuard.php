<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Services;

use App\Models\User;
use App\Modules\Accounting\Models\FiscalPeriod;
use App\Modules\Core\Services\AuditTrail;
use App\Modules\Core\Services\BusinessContext;
use Illuminate\Validation\ValidationException;

final class PeriodGuard
{
    public function handle(User $actor, string $date, bool $override = false, string $reason = ''): FiscalPeriod
    {
        $period = FiscalPeriod::query()->where('starts_on', '<=', $date)->where('ends_on', '>=', $date)->lockForUpdate()->first();
        if ($period === null) {
            throw ValidationException::withMessages(['entry_date' => __('accounting.missing_period')]);
        }
        if ($period->is_locked) {
            if (! $override || trim($reason) === '') {
                throw ValidationException::withMessages(['entry_date' => __('accounting.period_locked')]);
            }
            app(LedgerAccess::class)->authorize($actor, 'accounting.override-lock');
            app(AuditTrail::class)->record($period->company_id, app(BusinessContext::class)->branchId, $actor->id, 'period.override', FiscalPeriod::class, (string) $period->id, [], ['reason' => $reason]);
        }

        return $period;
    }
}
