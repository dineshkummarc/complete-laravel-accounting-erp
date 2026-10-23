<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CompanyAccess
{
    /** @var array<string, bool|null> */
    private array $memberships = [];

    public function isMember(User $actor, int $company): bool
    {
        return $this->membership($actor, $company) !== null;
    }

    public function isOwner(User $actor, ?int $company = null): bool
    {
        $company ??= app(BusinessContext::class)->companyId;

        return $company !== null && $this->membership($actor, $company) === true;
    }

    private function membership(User $actor, int $company): ?bool
    {
        $key = $company.':'.$actor->id;
        if (! array_key_exists($key, $this->memberships)) {
            $owner = DB::table('company_user')->join('companies', 'companies.id', '=', 'company_user.company_id')->where('companies.id', $company)->whereNull('companies.deleted_at')->where('user_id', $actor->id)->value('is_owner');
            $this->memberships[$key] = $owner === null ? null : (bool) $owner;
        }

        return $this->memberships[$key];
    }
}
