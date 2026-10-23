<?php

declare(strict_types=1);

namespace App\Modules\Core\Actions;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Services\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

final class UpdateCompany
{
    /** @param array<string, mixed> $data */
    public function handle(User $actor, Company $company, array $data): void
    {
        Gate::forUser($actor)->authorize('update', $company);
        $values = Validator::make($data, ['name' => 'required|string|max:255', 'locale' => 'required|in:en,bn', 'timezone' => 'required|timezone', 'fiscal_year_start_month' => 'required|integer|min:1|max:12'])->validate();
        DB::transaction(function () use ($actor, $company, $values): void {
            $before = $company->only(array_keys($values));
            $company->fill($values);
            $company->updated_by = $actor->id;
            $company->save();
            app(AuditTrail::class)->record($company->id, null, $actor->id, 'company.updated', Company::class, (string) $company->id, $before, $values);
        });
    }
}
