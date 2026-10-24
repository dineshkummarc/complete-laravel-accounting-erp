<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Modules\Accounting\Actions;
use App\Modules\Accounting\Models\FiscalPeriod;
use App\Modules\Accounting\Services\AccountChoices;
use App\Modules\Accounting\Services\LedgerAccess;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Services\BusinessContext;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

final class AccountingSettings extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string $view = 'filament.pages.accounting-settings';

    public static function getNavigationLabel(): string
    {
        return __('accounting.settings');
    }

    public function getTitle(): string
    {
        return __('accounting.settings');
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
        return $table->query(FiscalPeriod::query())->columns([TextColumn::make('name')->label(__('business.name')), TextColumn::make('starts_on')->label(__('reports.from'))->date(), TextColumn::make('ends_on')->label(__('reports.to'))->date(), IconColumn::make('is_locked')->label(__('accounting.locked'))->boolean()])->actions([
            TableAction::make('lock')->label(__('accounting.period_lock'))->visible(fn (): bool => app(LedgerAccess::class)->allows(auth()->user(), 'accounting.period.manage', false))->form([Toggle::make('locked')->label(__('accounting.locked'))->default(true), Textarea::make('reason')->label(__('accounting.reason'))->required()->maxLength(2000)])->action(fn (FiscalPeriod $record, array $data) => app(Actions\LockPeriod::class)->handle(auth()->user(), $record->id, $data['locked'], $data['reason'])),
        ])->defaultSort('starts_on', 'desc')->paginated([12, 24, 48]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('mapping')->label(__('accounting.account_mapping'))->form([
                Select::make('key')->label(__('accounting.mapping_key'))->options(array_combine(array_keys(config('account-mappings')), array_keys(config('account-mappings'))))->required(),
                Select::make('account_id')->label(__('accounting.account'))->options(fn (): array => app(AccountChoices::class)->all())->searchable()->required(),
                Toggle::make('branch_override')->label(__('accounting.branch_override'))->default(false),
            ])->action(fn (array $data) => app(Actions\SetAccountMapping::class)->handle(auth()->user(), $data['key'], (int) $data['account_id'], $data['branch_override'])),
            Action::make('year')->label(__('accounting.create_year'))->visible(fn (): bool => app(LedgerAccess::class)->allows(auth()->user(), 'accounting.period.manage', false))->form([DatePicker::make('start')->label(__('reports.from'))->required()])->action(fn (array $data) => app(Actions\CreateFiscalYear::class)->handle(auth()->user(), $data['start'])),
            Action::make('approval')->label(__('accounting.approval_settings'))->fillForm(function (): array {
                $company = Company::query()->findOrFail(app(BusinessContext::class)->companyId);

                return json_decode($company->getRawOriginal('accounting_settings') ?? '{}', true, flags: JSON_THROW_ON_ERROR) + ['maker_checker' => false, 'approval_threshold' => '0.0000'];
            })->form([Toggle::make('maker_checker')->label(__('accounting.maker_checker')), TextInput::make('approval_threshold')->label(__('accounting.approval_threshold'))->required()->inputMode('decimal')])->action(fn (array $data) => app(Actions\UpdateAccountingSettings::class)->handle(auth()->user(), $data)),
        ];
    }
}
