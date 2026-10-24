<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Actions;

use App\Models\User;
use App\Modules\Accounting\Models\TaxGroup;
use App\Modules\Accounting\Models\TaxRate;
use App\Modules\Accounting\Services\LedgerAccess;
use App\Modules\Core\Services\AuditTrail;
use App\Modules\Core\Services\BusinessContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class CreateTaxGroup
{
    /** @param list<int> $rateIds */
    public function handle(User $actor, string $name, array $rateIds): TaxGroup
    {
        app(LedgerAccess::class)->authorize($actor, 'accounting.account.manage', false);
        Validator::make(['name' => $name, 'rates' => $rateIds], ['name' => ['required', 'string', 'max:255', Rule::unique('tax_groups', 'name')->where('company_id', app(BusinessContext::class)->companyId)], 'rates' => 'required|array|min:1|max:20', 'rates.*' => 'required|integer|distinct'])->validate();

        return DB::transaction(function () use ($actor, $name, $rateIds): TaxGroup {
            if (TaxRate::query()->whereIn('id', $rateIds)->where('is_active', true)->count() !== count($rateIds)) {
                throw ValidationException::withMessages(['rates' => 'Choose active rates belonging to this company.']);
            }
            $group = new TaxGroup(['name' => $name]);
            $group->created_by = $actor->id;
            $group->save();
            foreach ($rateIds as $position => $id) {
                $group->rates()->attach($id, ['position' => $position]);
            }
            app(AuditTrail::class)->record($group->company_id, null, $actor->id, 'tax_group.created', TaxGroup::class, (string) $group->id, [], ['name' => $name, 'rates' => $rateIds]);

            return $group;
        });
    }
}
