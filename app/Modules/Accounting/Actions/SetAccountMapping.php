<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Actions;

use App\Models\User;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Services\LedgerAccess;
use App\Modules\Core\Services\AuditTrail;
use App\Modules\Core\Services\BusinessContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SetAccountMapping
{
    public function handle(User $actor, string $key, int $accountId, bool $branchOverride = false): void
    {
        app(LedgerAccess::class)->authorize($actor, 'accounting.account.manage', $branchOverride);
        if (! in_array($key, array_keys(config('account-mappings')), true)) {
            throw ValidationException::withMessages(['key' => __('accounting.unknown_mapping')]);
        }
        Account::query()->whereKey($accountId)->where('is_active', true)->where('is_group', false)->firstOrFail();
        $context = app(BusinessContext::class);
        DB::transaction(function () use ($actor, $key, $accountId, $branchOverride, $context): void {
            $scope = ['company_id' => $context->companyId, 'branch_id' => $branchOverride ? $context->branchId : 0, 'key' => $key];
            $old = DB::table('account_mappings')->where($scope)->first();
            DB::table('account_mappings')->updateOrInsert($scope, ['account_id' => $accountId, 'created_at' => now(), 'updated_at' => now()]);
            app(AuditTrail::class)->record($context->companyId, $branchOverride ? $context->branchId : null, $actor->id, 'account_mapping.updated', 'account_mapping', $key, ['account_id' => $old?->account_id], ['account_id' => $accountId]);
        });
    }
}
