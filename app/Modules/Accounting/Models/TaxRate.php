<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Models;

use App\Modules\Core\Models\BusinessModel;

final class TaxRate extends BusinessModel
{
    /** @return array{rate:'decimal:10',is_inclusive:'boolean',is_compound:'boolean',is_active:'boolean'} */
    protected function casts(): array
    {
        return ['rate' => 'decimal:10', 'is_inclusive' => 'boolean', 'is_compound' => 'boolean', 'is_active' => 'boolean'];
    }
}
