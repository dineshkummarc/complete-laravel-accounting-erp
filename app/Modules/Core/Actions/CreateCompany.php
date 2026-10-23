<?php

declare(strict_types=1);

namespace App\Modules\Core\Actions;

use App\Models\User;
use App\Modules\Core\Models\Branch;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Modules\ModuleManager;
use App\Modules\Core\Services\AuditTrail;
use App\Modules\Core\Services\BusinessContext;
use App\Modules\Core\Services\LocalizationPack;
use Brick\Money\Currency;
use Brick\Money\Exception\UnknownCurrencyException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CreateCompany
{
    /** @param array<string, mixed> $data */
    public function handle(User $owner, array $data): Company
    {
        $values = Validator::make($data, ['name' => 'required|string|max:255', 'currency' => 'required|string|size:3',
            'locale' => 'required|in:en,bn', 'timezone' => 'required|timezone', 'localization_pack' => 'required|in:bd,generic', 'preset' => 'sometimes|in:'.implode(',', array_keys(config('presets')))])->validate();

        try {
            Currency::of($values['currency']);
        } catch (UnknownCurrencyException) {
            throw ValidationException::withMessages(['currency' => __('business.invalid_currency')]);
        }
        $values['fiscal_year_start_month'] = app(LocalizationPack::class)->defaults($values['localization_pack'])['fiscal_year_start_month'];
        $preset = $values['preset'] ?? 'custom';
        unset($values['preset']);

        return DB::transaction(function () use ($owner, $values, $preset): Company {
            $company = Company::create($values + ['uuid' => (string) Str::uuid(), 'created_by' => $owner->id]);
            $company->users()->attach($owner->id, ['is_owner' => true]);
            app(BusinessContext::class)->run($company->id, null, function () use ($company, $owner): void {
                $branch = new Branch(['name' => $company->name, 'code' => 'HQ', 'timezone' => $company->timezone]);
                $branch->created_by = $owner->id;
                $branch->save();
                foreach (['core', 'accounting'] as $module) {
                    DB::table('company_modules')->insert(['company_id' => $company->id, 'module_key' => $module, 'enabled' => true,
                        'enabled_at' => now(), 'enabled_by' => $owner->id, 'created_at' => now(), 'updated_at' => now()]);
                }
                $owner->forceFill(['current_company_id' => $company->id, 'current_branch_id' => $branch->id])->save();
            });
            app(SeedCompanyRoles::class)->handle($owner, $company->id);
            app(ModuleManager::class)->preset($owner, $company->id, $preset);
            app(AuditTrail::class)->record($company->id, null, $owner->id, 'company.created', Company::class, (string) $company->id, [], $values);

            return $company;
        });
    }
}
