<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Actions;

use App\Models\User;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Services\LedgerAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SeedChartOfAccounts
{
    public function handle(User $actor, string $template = 'generic-trading'): void
    {
        app(LedgerAccess::class)->authorize($actor, 'accounting.account.manage', false);
        if (! in_array($template, ['generic-trading', 'generic-service', 'manufacturing', 'bd-standard'], true)) {
            throw ValidationException::withMessages(['template' => __('accounting.unknown_template')]);
        }
        DB::transaction(function () use ($actor, $template): void {
            foreach (config('coa-templates') as $row) {
                if ($template === 'generic-service' && in_array($row['code'], ['1300', '1350', '2500', '5100'], true)) {
                    continue;
                }
                if (! Account::query()->where('code', $row['code'])->exists()) {
                    app(CreateAccount::class)->handle($actor, $row + ['is_cash_equivalent' => in_array($row['code'], ['1100', '1110', '1120'], true)]);
                }
            }
            foreach (config('account-mappings') as $key => $code) {
                $account = Account::query()->where('code', $code)->first();
                if ($account !== null && ! DB::table('account_mappings')->where('company_id', $account->company_id)->where('branch_id', 0)->where('key', $key)->exists()) {
                    app(SetAccountMapping::class)->handle($actor, $key, $account->id);
                }
            }
        });
    }
}
