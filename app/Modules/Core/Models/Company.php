<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use ImranDev\UniversalSlug\Attributes\Slug;
use ImranDev\UniversalSlug\Traits\HasUniversalSlug;
use OwenIt\Auditing\Auditable;

#[Slug(from: 'name', to: 'slug', immutable: true)]
final class Company extends Model implements \OwenIt\Auditing\Contracts\Auditable
{
    use Auditable;
    use HasUniversalSlug, SoftDeletes;

    protected $guarded = ['id'];

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('is_owner')->withTimestamps();
    }

    /** @return HasMany<Role, $this> */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class, 'team_id');
    }
}
