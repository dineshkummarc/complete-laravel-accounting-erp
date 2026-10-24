<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\Account;
use App\Modules\Core\Services\BusinessContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AccountMappings
{
    public function get(string $key): Account
    {
        $context = app(BusinessContext::class);
        $id = DB::table('account_mappings')->where('company_id', $context->companyId)->where('key', $key)->whereIn('branch_id', [$context->branchId ?? 0, 0])->orderByDesc('branch_id')->value('account_id');
        $account = Account::query()->whereKey($id)->where('is_active', true)->where('is_group', false)->first();
        if ($account === null) {
            throw ValidationException::withMessages(['mapping' => __('accounting.missing_mapping', ['key' => $key])]);
        }

        return $account;
    }
}
