<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Enums;

enum AccountType: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Income = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return __('accounting.type_'.$this->value);
    }

    public function normalBalance(): string
    {
        return in_array($this, [self::Asset, self::Expense], true) ? 'debit' : 'credit';
    }
}
