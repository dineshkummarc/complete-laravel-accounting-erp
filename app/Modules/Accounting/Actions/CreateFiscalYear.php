<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Actions;

use App\Models\User;
use App\Modules\Accounting\Models\FiscalPeriod;
use App\Modules\Accounting\Models\FiscalYear;
use App\Modules\Accounting\Services\LedgerAccess;
use App\Modules\Core\Services\AuditTrail;
use App\Modules\Core\Services\BusinessContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class CreateFiscalYear
{
    public function handle(User $actor, string $start): FiscalYear
    {
        app(LedgerAccess::class)->authorize($actor, 'accounting.period.manage', false);
        Validator::make(['starts_on' => $start], ['starts_on' => 'required|date_format:Y-m-d'])->validate();
        $begin = CarbonImmutable::parse($start)->startOfDay();
        if ($begin->day !== 1) {
            throw ValidationException::withMessages(['starts_on' => __('accounting.year_first_day')]);
        }
        $end = $begin->addYear()->subDay();

        return DB::transaction(function () use ($actor, $begin, $end): FiscalYear {
            DB::table('companies')->where('id', app(BusinessContext::class)->companyId)->lockForUpdate()->firstOrFail();
            if (FiscalYear::query()->where('starts_on', '<=', $end->toDateString())->where('ends_on', '>=', $begin->toDateString())->exists()) {
                throw ValidationException::withMessages(['starts_on' => __('accounting.year_overlap')]);
            }
            $year = new FiscalYear(['name' => $begin->format('Y').' / '.$end->format('Y'), 'starts_on' => $begin->toDateString(), 'ends_on' => $end->toDateString()]);
            $year->created_by = $actor->id;
            $year->save();
            for ($month = 0; $month < 12; $month++) {
                $start = $begin->addMonths($month);
                $period = new FiscalPeriod(['fiscal_year_id' => $year->id, 'name' => $start->format('Y-m'), 'starts_on' => $start->toDateString(), 'ends_on' => $start->endOfMonth()->toDateString()]);
                $period->created_by = $actor->id;
                $period->save();
            }
            app(AuditTrail::class)->record($year->company_id, null, $actor->id, 'fiscal_year.created', FiscalYear::class, (string) $year->id, [], ['starts_on' => $begin->toDateString(), 'ends_on' => $end->toDateString()]);

            return $year;
        });
    }
}
