<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Modules\Accounting\Actions\CreateTaxGroup;
use App\Modules\Accounting\Actions\CreateTaxRate;
use App\Modules\Accounting\Models\TaxGroup;
use App\Modules\Accounting\Models\TaxRate;
use App\Modules\Accounting\Services\AccountChoices;
use App\Modules\Accounting\Services\LedgerAccess;
use App\Modules\Accounting\Services\TaxCalculator;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

final class TaxRates extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static string $view = 'filament.pages.tax-rates';

    public static function getNavigationLabel(): string
    {
        return __('tax.rates');
    }

    public function getTitle(): string
    {
        return __('tax.rates');
    }

    public static function canAccess(): bool
    {
        return auth()->check() && app(LedgerAccess::class)->allows(auth()->user(), 'accounting.account.manage', false);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canAccess();
    }

    public function table(Table $table): Table
    {
        return $table->query(TaxRate::query())->columns([TextColumn::make('name')->label(__('business.name'))->searchable(), TextColumn::make('rate')->label(__('tax.rate')), IconColumn::make('is_inclusive')->label(__('tax.inclusive'))->boolean(), IconColumn::make('is_compound')->label(__('tax.compound'))->boolean(), IconColumn::make('is_withholding')->label(__('tax.withholding'))->boolean()])->paginated([25, 50, 100]);
    }

    protected function getHeaderActions(): array
    {
        $rates = TaxRate::query()->where('is_active', true)->pluck('name', 'id')->all();

        return [
            Action::make('create')->label(__('tax.create_rate'))->form([
                TextInput::make('name')->label(__('business.name'))->required()->maxLength(255), TextInput::make('rate')->label(__('tax.rate'))->required()->inputMode('decimal'),
                Toggle::make('is_inclusive')->label(__('tax.inclusive')), Toggle::make('is_compound')->label(__('tax.compound')), Toggle::make('is_withholding')->label(__('tax.withholding')),
                Select::make('input_account_id')->label(__('tax.input_account'))->options(fn (): array => app(AccountChoices::class)->all())->searchable(), Select::make('output_account_id')->label(__('tax.output_account'))->options(fn (): array => app(AccountChoices::class)->all())->searchable(),
            ])->action(fn (array $data) => app(CreateTaxRate::class)->handle(auth()->user(), $data)),
            Action::make('group')->label(__('tax.create_group'))->form([
                TextInput::make('name')->label(__('business.name'))->required()->maxLength(255),
                Repeater::make('rates')->label(__('tax.ordered_rates'))->schema([Select::make('rate_id')->label(__('tax.rate'))->options($rates)->required()])->minItems(1)->maxItems(20)->defaultItems(1),
            ])->action(fn (array $data) => app(CreateTaxGroup::class)->handle(auth()->user(), $data['name'], array_map(fn (array $row): int => (int) $row['rate_id'], array_values($data['rates'])))),
            Action::make('calculate')->label(__('tax.calculate'))->form([
                Select::make('group_id')->label(__('tax.group'))->options(fn (): array => TaxGroup::query()->where('is_active', true)->pluck('name', 'id')->all())->required(),
                Textarea::make('amounts')->label(__('tax.amounts'))->required()->maxLength(100000),
                Select::make('rounding')->label(__('tax.rounding'))->options(['line' => __('tax.line'), 'document' => __('tax.document')])->default('line')->required(),
                Select::make('scale')->label(__('tax.decimals'))->options([0 => '0', 1 => '1', 2 => '2', 3 => '3', 4 => '4'])->default(2)->required(),
            ])->action(function (array $data): void {
                $amounts = array_values(array_filter(array_map(trim(...), explode("\n", $data['amounts'])), fn (string $line): bool => $line !== ''));
                $result = app(TaxCalculator::class)->calculate(auth()->user(), (int) $data['group_id'], $amounts, $data['rounding'], (int) $data['scale']);
                Notification::make()->title(__('tax.calculation'))->body(__('tax.net').': '.$result['net'].' · '.__('tax.tax').': '.$result['tax'].' · '.__('tax.withholding').': '.$result['withholding'].' · '.__('tax.payable').': '.$result['payable'])->persistent()->send();
            }),
        ];
    }
}
