<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Modules\Core\Services\BusinessContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

final class AuditEvent extends Model
{
    protected $guarded = ['*'];

    protected static function booted(): void
    {
        self::addGlobalScope('company', fn (Builder $q) => $q->where('company_id', app(BusinessContext::class)->companyId ?? 0));
        foreach (['creating', 'updating', 'deleting'] as $event) {
            self::$event(fn () => throw new LogicException('Audit events are immutable.'));
        }
    }
}
