<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Models;

use App\Modules\Core\Models\BusinessModel;

final class ExchangeRate extends BusinessModel
{
    /** @return array{rate_date:'immutable_date',rate:'decimal:10'} */
    protected function casts(): array
    {
        return ['rate_date' => 'immutable_date', 'rate' => 'decimal:10'];
    }
}
