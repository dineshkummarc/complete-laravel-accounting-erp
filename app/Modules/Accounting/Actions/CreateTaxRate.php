<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Actions;

use App\Models\User;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\TaxRate;
use App\Modules\Accounting\Services\LedgerAccess;
use App\Modules\Core\Services\AuditTrail;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class CreateTaxRate
{
    /** @param array<string,mixed> $input */
    public function handle(User $actor, array $input): TaxRate
    {
        app(LedgerAccess::class)->authorize($actor, 'accounting.account.manage', false);
        $data = Validator::make($input, ['name' => 'required|string|max:255', 'rate' => 'required|string|regex:/^\d{1,3}(?:\.\d{1,10})?$/D', 'is_inclusive' => 'sometimes|boolean', 'is_compound' => 'sometimes|boolean', 'is_withholding' => 'sometimes|boolean', 'input_account_id' => 'nullable|integer', 'output_account_id' => 'nullable|integer'])->validate();
        if (BigDecimal::of($data['rate'])->isGreaterThan('100') || (($data['is_withholding'] ?? false) && (($data['is_inclusive'] ?? false) || ($data['is_compound'] ?? false)))) {
            throw ValidationException::withMessages(['rate' => 'Rates must be between 0 and 100. Withholding cannot be inclusive or compound.']);
        }
        foreach (['input_account_id', 'output_account_id'] as $key) {
            if (! empty($data[$key])) {
                Account::query()->whereKey($data[$key])->where('is_active', true)->where('is_group', false)->firstOrFail();
            }
        }

        return DB::transaction(function () use ($actor, $data): TaxRate {
            $rate = new TaxRate($data);
            $rate->created_by = $actor->id;
            $rate->save();
            app(AuditTrail::class)->record($rate->company_id, null, $actor->id, 'tax_rate.created', TaxRate::class, (string) $rate->id, [], $data);

            return $rate;
        });
    }
}
