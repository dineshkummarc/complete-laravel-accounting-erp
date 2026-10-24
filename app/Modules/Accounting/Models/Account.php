<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Models;

use App\Modules\Accounting\Enums\AccountType;
use App\Modules\Core\Models\BusinessModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

/** @property bool $is_cash_equivalent */
final class Account extends BusinessModel implements Auditable
{
    use \OwenIt\Auditing\Auditable, SoftDeletes;

    /** @return array{type:class-string<AccountType>,is_active:'boolean',is_group:'boolean',is_cash_equivalent:'boolean'} */
    protected function casts(): array
    {
        return ['type' => AccountType::class, 'is_active' => 'boolean', 'is_group' => 'boolean', 'is_cash_equivalent' => 'boolean'];
    }
}
