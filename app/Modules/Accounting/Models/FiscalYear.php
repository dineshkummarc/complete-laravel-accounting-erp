<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Models;

use App\Modules\Core\Models\BusinessModel;

final class FiscalYear extends BusinessModel
{
    /** @return array{starts_on:'immutable_date',ends_on:'immutable_date'} */
    protected function casts(): array
    {
        return ['starts_on' => 'immutable_date', 'ends_on' => 'immutable_date'];
    }
}
