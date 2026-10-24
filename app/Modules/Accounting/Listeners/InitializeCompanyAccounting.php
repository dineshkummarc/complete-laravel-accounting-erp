<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Listeners;

use App\Models\User;
use App\Modules\Accounting\Actions\CreateFiscalYear;
use App\Modules\Accounting\Actions\SeedChartOfAccounts;
use App\Modules\Core\Events\CompanyCreated;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Services\BusinessContext;
use Carbon\CarbonImmutable;

final class InitializeCompanyAccounting
{
    public function handle(CompanyCreated $event): void
    {
        $owner = User::query()->findOrFail($event->ownerId);
        $company = Company::query()->findOrFail($event->companyId);
        app(BusinessContext::class)->run($company->id, $owner->current_branch_id, function () use ($owner, $company): void {
            app(SeedChartOfAccounts::class)->handle($owner, $company->localization_pack === 'bd' ? 'bd-standard' : 'generic-trading');
            $today = CarbonImmutable::now($company->timezone);
            $month = $company->fiscal_year_start_month;
            $year = $today->month >= $month ? $today->year : $today->year - 1;
            app(CreateFiscalYear::class)->handle($owner, sprintf('%04d-%02d-01', $year, $month));
        });
    }
}
