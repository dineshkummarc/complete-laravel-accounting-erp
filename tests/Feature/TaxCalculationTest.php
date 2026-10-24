<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Accounting\Actions\CreateTaxGroup;
use App\Modules\Accounting\Actions\CreateTaxRate;
use App\Modules\Accounting\Services\TaxCalculator;
use App\Modules\Core\Services\BusinessContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->company = businessCompany($this->owner);
    $this->actingAs($this->owner->fresh());
    app(BusinessContext::class)->select(auth()->user(), $this->company->id, auth()->user()->current_branch_id);
});

it('extracts inclusive tax and snapshots the configured facts', function () {
    $rate = app(CreateTaxRate::class)->handle(auth()->user(), ['name' => 'Configured inclusive example', 'rate' => '15', 'is_inclusive' => true]);
    $group = app(CreateTaxGroup::class)->handle(auth()->user(), 'Inclusive group', [$rate->id]);
    $result = app(TaxCalculator::class)->calculate(auth()->user(), $group->id, ['115.0000']);
    expect($result['net'])->toBe('100.0000')->and($result['tax'])->toBe('15.0000')->and($result['payable'])->toBe('115.0000')->and($result['components'][0]['tax_rate_id'])->toBe($rate->id);
});

it('applies ordered compound tax and separate withholding to the net base', function () {
    $first = app(CreateTaxRate::class)->handle(auth()->user(), ['name' => 'First', 'rate' => '10']);
    $second = app(CreateTaxRate::class)->handle(auth()->user(), ['name' => 'Compound', 'rate' => '5', 'is_compound' => true]);
    $withholding = app(CreateTaxRate::class)->handle(auth()->user(), ['name' => 'Withholding', 'rate' => '2', 'is_withholding' => true]);
    $group = app(CreateTaxGroup::class)->handle(auth()->user(), 'Compound group', [$first->id, $second->id, $withholding->id]);
    $result = app(TaxCalculator::class)->calculate(auth()->user(), $group->id, ['100']);
    expect($result['tax'])->toBe('15.5000')->and($result['withholding'])->toBe('2.0000')->and($result['total'])->toBe('115.5000')->and($result['payable'])->toBe('113.5000');
});

it('supports distinct line and document rounding without binary arithmetic', function () {
    $rate = app(CreateTaxRate::class)->handle(auth()->user(), ['name' => 'Rounding', 'rate' => '10']);
    $group = app(CreateTaxGroup::class)->handle(auth()->user(), 'Rounding group', [$rate->id]);
    expect(app(TaxCalculator::class)->calculate(auth()->user(), $group->id, ['0.05', '0.05'], 'line')['tax'])->toBe('0.0200');
    expect(app(TaxCalculator::class)->calculate(auth()->user(), $group->id, ['0.05', '0.05'], 'document')['tax'])->toBe('0.0100');
});

it('preserves inclusive gross totals when multiple tax components round', function () {
    $first = app(CreateTaxRate::class)->handle(auth()->user(), ['name' => 'One', 'rate' => '100', 'is_inclusive' => true]);
    $second = app(CreateTaxRate::class)->handle(auth()->user(), ['name' => 'Two', 'rate' => '100', 'is_inclusive' => true]);
    $group = app(CreateTaxGroup::class)->handle(auth()->user(), 'Inclusive rounding', [$first->id, $second->id]);
    foreach (['line', 'document'] as $rounding) {
        expect(app(TaxCalculator::class)->calculate(auth()->user(), $group->id, ['0.02'], $rounding)['total'])->toBe('0.0200');
    }
});

it('rejects cross-company rates and invalid withholding flags', function () {
    $rate = app(CreateTaxRate::class)->handle(auth()->user(), ['name' => 'Private', 'rate' => '10']);
    $other = businessCompany($this->owner, 'Other tax business');
    app(BusinessContext::class)->select(auth()->user(), $other->id, $this->owner->fresh()->current_branch_id);
    expect(fn () => app(CreateTaxGroup::class)->handle(auth()->user(), 'Wrong company', [$rate->id]))->toThrow(ValidationException::class);
    expect(fn () => app(CreateTaxRate::class)->handle(auth()->user(), ['name' => 'Invalid', 'rate' => '1', 'is_withholding' => true, 'is_inclusive' => true]))->toThrow(ValidationException::class);
});

it('returns validation errors for malformed amounts and duplicate group names', function () {
    $rate = app(CreateTaxRate::class)->handle(auth()->user(), ['name' => 'Rate', 'rate' => '10']);
    $group = app(CreateTaxGroup::class)->handle(auth()->user(), 'Unique name', [$rate->id]);
    expect(fn () => app(TaxCalculator::class)->calculate(auth()->user(), $group->id, ['1e3']))->toThrow(ValidationException::class);
    expect(fn () => app(CreateTaxGroup::class)->handle(auth()->user(), 'Unique name', [$rate->id]))->toThrow(ValidationException::class);
});
