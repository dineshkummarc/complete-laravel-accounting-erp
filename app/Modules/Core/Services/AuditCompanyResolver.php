<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\Company;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\Resolver;

final class AuditCompanyResolver implements Resolver
{
    public static function resolve(Auditable $auditable): ?int
    {
        if ($auditable instanceof Company) {
            return $auditable->id;
        }
        if ($auditable instanceof Model) {
            $id = $auditable->getAttribute('company_id') ?? $auditable->getAttribute('team_id') ?? app(BusinessContext::class)->companyId;

            return $id === null ? null : (int) $id;
        }

        return app(BusinessContext::class)->companyId;
    }
}
