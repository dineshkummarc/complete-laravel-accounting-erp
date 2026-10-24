<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Actions;

use App\Models\User;
use App\Modules\Accounting\Services\LedgerAccess;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Services\AuditTrail;
use App\Modules\Core\Services\BusinessContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class UpdateAccountingSettings
{
    /** @param array<string,mixed> $settings */
    public function handle(User $actor, array $settings): void
    {
        app(LedgerAccess::class)->authorize($actor, 'accounting.account.manage', false);
        $data = Validator::make($settings, ['maker_checker' => 'required|boolean', 'approval_threshold' => 'required|regex:/^\d{1,16}(?:\.\d{1,4})?$/D'])->validate();
        DB::transaction(function () use ($actor, $data): void {
            $company = Company::query()->lockForUpdate()->findOrFail(app(BusinessContext::class)->companyId);
            $old = json_decode($company->getRawOriginal('accounting_settings') ?? '{}', true, flags: JSON_THROW_ON_ERROR);
            $next = array_replace($old, $data);
            $company->accounting_settings = json_encode($next, JSON_THROW_ON_ERROR);
            $company->save();
            app(AuditTrail::class)->record($company->id, null, $actor->id, 'accounting_settings.updated', Company::class, (string) $company->id, $old, $next);
        });
    }
}
