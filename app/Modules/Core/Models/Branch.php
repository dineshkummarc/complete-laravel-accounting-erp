<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use ImranDev\UniversalSlug\SlugOptions;
use ImranDev\UniversalSlug\Traits\HasUniversalSlug;
use OwenIt\Auditing\Auditable;

final class Branch extends BusinessModel implements \OwenIt\Auditing\Contracts\Auditable
{
    use Auditable;
    use HasUniversalSlug, SoftDeletes;

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug')->protectReserved(true)->includeTrashed(true)
            ->uniqueScope(fn ($query) => $query->where('company_id', $this->company_id));
    }
}
