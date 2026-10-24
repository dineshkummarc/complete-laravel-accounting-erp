<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\Account;
use App\Modules\Core\Services\BusinessContext;

final class AccountChoices
{
    /** @var array<int,array<int,string>> */
    private array $choices = [];

    /** @return array<int,string> */
    public function all(): array
    {
        $company = app(BusinessContext::class)->companyId;
        if ($company === null) {
            return [];
        }

        return $this->choices[$company] ??= Account::query()->where('is_active', true)->where('is_group', false)->orderBy('code')->get()
            ->mapWithKeys(fn (Account $account): array => [$account->id => $account->code.' · '.(app()->getLocale() === 'bn' ? ($account->name_bn ?? $account->name) : $account->name)])->all();
    }
}
