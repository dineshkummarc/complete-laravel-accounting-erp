<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Actions;

use App\Models\User;
use App\Modules\Accounting\Enums\AccountType;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Services\LedgerAccess;
use App\Modules\Core\Services\AuditTrail;
use App\Modules\Core\Services\BusinessContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class CreateAccount
{
    /** @param array<string,mixed> $data */
    public function handle(User $actor, array $data): Account
    {
        app(LedgerAccess::class)->authorize($actor, 'accounting.account.manage', false);
        $values = Validator::make($data, ['code' => ['required', 'string', 'max:30', Rule::unique('accounts', 'code')->where('company_id', app(BusinessContext::class)->companyId)],
            'name' => 'required|string|max:255', 'name_bn' => 'nullable|string|max:255', 'type' => ['required', Rule::enum(AccountType::class)],
            'normal_balance' => 'required|in:debit,credit', 'is_cash_equivalent' => 'sometimes|boolean', 'is_group' => 'sometimes|boolean', 'parent_id' => ['nullable', Rule::exists('accounts', 'id')->where('company_id', app(BusinessContext::class)->companyId)]])->validate();

        return DB::transaction(function () use ($actor, $values): Account {
            $account = new Account($values);
            $account->created_by = $actor->id;
            $account->save();
            app(AuditTrail::class)->record($account->company_id, null, $actor->id, 'account.created', Account::class, (string) $account->id, [], $values);

            return $account;
        });
    }
}
