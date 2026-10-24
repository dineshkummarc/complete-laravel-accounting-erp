<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Modules\Core\Services\BusinessContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $company_id
 * @property int|null $branch_id
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property string $uuid
 */
abstract class BusinessModel extends Model
{
    protected $guarded = ['id', 'company_id', 'branch_id', 'created_by', 'updated_by'];

    protected static bool $branchScoped = false;

    /** @return BelongsTo<Company,$this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Persist date-only casts without a time on every database driver. */
    public function setAttribute($key, $value)
    {
        parent::setAttribute($key, $value);
        $cast = $this->getCasts()[$key] ?? '';
        if ($value !== null && in_array(explode(':', $cast)[0], ['date', 'immutable_date'], true)) {
            $this->attributes[$key] = $this->asDate($value)->format('Y-m-d');
        }

        return $this;
    }

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $query): void {
            $context = app(BusinessContext::class);
            $context->companyId === null ? $query->whereRaw('1=0') : $query->where($query->qualifyColumn('company_id'), $context->companyId);
        });
        if (static::$branchScoped) {
            static::addGlobalScope('branch', function (Builder $query): void {
                $branch = app(BusinessContext::class)->branchId;
                $branch === null ? $query->whereRaw('1=0') : $query->where($query->qualifyColumn('branch_id'), $branch);
            });
        }
        static::creating(function (self $model): void {
            $context = app(BusinessContext::class);
            if ($context->companyId === null || static::$branchScoped && $context->branchId === null) {
                throw new AuthorizationException('An authorized business context is required.');
            }
            $model->company_id = $context->companyId;
            if (static::$branchScoped) {
                $model->branch_id = $context->branchId;
            }
            $model->uuid ??= (string) Str::uuid();
            $model->created_by ??= auth()->id();
        });
        static::updating(function (self $model): void {
            if ($model->isDirty(['company_id', 'branch_id', 'uuid', 'created_by'])) {
                throw new AuthorizationException('Record ownership is immutable.');
            }
            $model->updated_by = app(BusinessContext::class)->actorId ?? auth()->id();
        });
    }
}
