<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Models;

use App\Modules\Core\Models\BusinessModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class TaxGroup extends BusinessModel
{
    /** @return array{is_active:'boolean'} */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return BelongsToMany<TaxRate,$this> */
    public function rates(): BelongsToMany
    {
        return $this->belongsToMany(TaxRate::class, 'tax_group_rate')->withPivot('position')->orderByPivot('position');
    }
}
