<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\Core\Models\Company;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class User extends Authenticatable implements FilamentUser, HasDefaultTenant, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, \Laravel\Sanctum\HasApiTokens, Notifiable, \Spatie\Permission\Traits\HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasPlatformPermission('app.access') || DB::table('company_user')->where('user_id', $this->id)->exists();
    }

    /** @return Collection<int, Company> */
    public function getTenants(Panel $panel): Collection
    {
        return Company::query()->whereHas('users', fn ($query) => $query->where('users.id', $this->id))->get();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $tenant instanceof Company && $tenant->users()->where('users.id', $this->id)->exists();
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        $company = Company::query()->find($this->current_company_id);

        return $company !== null && $this->canAccessTenant($company) ? $company : null;
    }

    public function hasPlatformPermission(string $permission): bool
    {
        $previous = getPermissionsTeamId();
        setPermissionsTeamId(0);
        $this->unsetRelation('roles')->unsetRelation('permissions');
        try {
            return $this->hasPermissionTo($permission, 'web');
        } catch (PermissionDoesNotExist) {
            return false;
        } finally {
            setPermissionsTeamId($previous);
            $this->unsetRelation('roles')->unsetRelation('permissions');
        }
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
