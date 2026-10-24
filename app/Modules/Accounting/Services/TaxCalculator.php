<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Services;

use App\Models\User;
use App\Modules\Accounting\Models\TaxGroup;
use App\Modules\Accounting\ValueObjects\Amount;
use Brick\Math\BigDecimal;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use Illuminate\Validation\ValidationException;

final class TaxCalculator
{
    /** @param list<string> $amounts
     * @return array{net:string,tax:string,withholding:string,total:string,payable:string,components:list<array<string,string|int|bool>>,rounding:string,scale:int}
     */
    public function calculate(User $actor, int $groupId, array $amounts, string $rounding = 'line', int $scale = 2): array
    {
        app(LedgerAccess::class)->authorize($actor, 'accounting.view', false);
        if (! in_array($rounding, ['line', 'document'], true) || $scale < 0 || $scale > 4 || $amounts === [] || count($amounts) > 10000) {
            throw ValidationException::withMessages(['tax' => 'Invalid tax calculation parameters.']);
        }
        $group = TaxGroup::query()->where('is_active', true)->with('rates')->findOrFail($groupId);
        $rates = $group->rates;
        if ($rates->isEmpty() || $rates->contains(fn ($rate): bool => ! $rate->is_active)) {
            throw ValidationException::withMessages(['tax' => 'The tax group has no rates or includes an inactive rate.']);
        }
        $coefficients = [];
        $previous = BigRational::zero();
        $inclusive = BigRational::one();
        foreach ($rates as $rate) {
            $coefficient = BigRational::of($rate->rate)->dividedBy(100)->multipliedBy($rate->is_compound ? BigRational::one()->plus($previous) : BigRational::one());
            $coefficients[$rate->id] = $coefficient;
            if ($rate->is_inclusive) {
                $inclusive = $inclusive->plus($coefficient);
            }
            if (! $rate->is_withholding) {
                $previous = $previous->plus($coefficient);
            }
        }
        $lastInclusive = $rates->last(fn ($rate): bool => $rate->is_inclusive)?->id;
        $gross = BigRational::zero();
        $net = BigRational::zero();
        $taxes = [];
        foreach ($amounts as $amount) {
            try {
                $inputAmount = (new Amount($amount))->value;
            } catch (\InvalidArgumentException $exception) {
                throw ValidationException::withMessages(['amounts' => $exception->getMessage()]);
            }
            $gross = $gross->plus($inputAmount);
            $base = $inputAmount->toBigRational()->dividedBy($inclusive);
            $includedRounded = $base->toScale($scale, RoundingMode::HalfUp);
            $net = $net->plus($rounding === 'line' ? $base->toScale($scale, RoundingMode::HalfUp) : $base);
            foreach ($rates as $rate) {
                $tax = $base->multipliedBy($coefficients[$rate->id]);
                if ($rate->is_inclusive) {
                    $includedRounded = $includedRounded->plus($tax->toScale($scale, RoundingMode::HalfUp));
                }
                $taxes[$rate->id] = ($taxes[$rate->id] ?? BigRational::zero())->plus($rounding === 'line' ? $tax->toScale($scale, RoundingMode::HalfUp) : $tax);
            }
            if ($rounding === 'line' && $lastInclusive !== null) {
                $taxes[$lastInclusive] = $taxes[$lastInclusive]->plus($inputAmount->toScale($scale, RoundingMode::HalfUp)->minus($includedRounded));
            }
        }
        $netAmount = $net->toScale($scale, RoundingMode::HalfUp);
        if ($rounding === 'document' && $lastInclusive !== null) {
            $includedRounded = $netAmount;
            foreach ($rates as $rate) {
                if ($rate->is_inclusive) {
                    $includedRounded = $includedRounded->plus($taxes[$rate->id]->toScale($scale, RoundingMode::HalfUp));
                }
            }
            $taxes[$lastInclusive] = $taxes[$lastInclusive]->toScale($scale, RoundingMode::HalfUp)->toBigRational()->plus($gross->toScale($scale, RoundingMode::HalfUp)->minus($includedRounded));
        }
        $taxAmount = BigDecimal::zero();
        $withholding = BigDecimal::zero();
        $components = [];
        foreach ($rates as $rate) {
            $amount = $taxes[$rate->id]->toScale($scale, RoundingMode::HalfUp);
            if ($rate->is_withholding) {
                $withholding = $withholding->plus($amount);
            } else {
                $taxAmount = $taxAmount->plus($amount);
            }
            $components[] = ['tax_rate_id' => $rate->id, 'name' => $rate->name, 'rate' => $rate->rate, 'inclusive' => $rate->is_inclusive, 'compound' => $rate->is_compound, 'withholding' => $rate->is_withholding, 'amount' => (string) $amount->toScale(4), 'input_account_id' => $rate->input_account_id ?? 0, 'output_account_id' => $rate->output_account_id ?? 0];
        }
        $total = $netAmount->plus($taxAmount);

        return ['net' => (string) $netAmount->toScale(4), 'tax' => (string) $taxAmount->toScale(4), 'withholding' => (string) $withholding->toScale(4), 'total' => (string) $total->toScale(4), 'payable' => (string) $total->minus($withholding)->toScale(4), 'components' => $components, 'rounding' => $rounding, 'scale' => $scale];
    }
}
