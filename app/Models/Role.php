<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Core\Models\Company;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;

final class Role extends \Spatie\Permission\Models\Role implements \OwenIt\Auditing\Contracts\Auditable
{
    use Auditable;

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'team_id');
    }
}
