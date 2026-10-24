<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Models;

use App\Modules\Core\Models\BusinessModel;

final class FiscalPeriod extends BusinessModel
{
    /** @return array{starts_on:'immutable_date',ends_on:'immutable_date',is_locked:'boolean'} */
    protected function casts(): array
    {
        return ['starts_on' => 'immutable_date', 'ends_on' => 'immutable_date', 'is_locked' => 'boolean'];
    }
}
