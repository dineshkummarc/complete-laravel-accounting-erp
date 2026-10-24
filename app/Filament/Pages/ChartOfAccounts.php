<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Modules\Accounting\Actions\CreateAccount;
use App\Modules\Accounting\Enums\AccountType;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Services\LedgerAccess;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

final class ChartOfAccounts extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string $view = 'filament.pages.chart-of-accounts';

    public static function getNavigationLabel(): string
    {
        return __('accounting.chart_of_accounts');
    }

    public function getTitle(): string
    {
        return __('accounting.chart_of_accounts');
    }

    public static function canAccess(): bool
    {
        return auth()->check() && app(LedgerAccess::class)->allows(auth()->user(), 'accounting.view', false);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canAccess();
    }

    public function table(Table $table): Table
    {
        return $table->query(Account::query())->columns([
            TextColumn::make('code')->label(__('accounting.code'))->sortable()->searchable(),
            TextColumn::make('name')->label(__('accounting.account'))->searchable()->formatStateUsing(fn (string $state, Account $record): string => app()->getLocale() === 'bn' ? ($record->name_bn ?? $state) : $state),
            TextColumn::make('type')->label(__('accounting.type'))->formatStateUsing(fn (AccountType $state): string => $state->label())->badge(),
        ])->defaultSort('code')->paginated([25, 50, 100]);
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('create')->label(__('accounting.create_account'))->visible(fn (): bool => auth()->user()->can('create', Account::class))->form([
            TextInput::make('code')->label(__('accounting.code'))->required()->maxLength(30), TextInput::make('name')->label(__('business.name'))->required()->maxLength(255),
            TextInput::make('name_bn')->label(__('accounting.bangla_name'))->maxLength(255),
            Select::make('type')->label(__('accounting.type'))->options(collect(AccountType::cases())->mapWithKeys(fn (AccountType $type): array => [$type->value => $type->label()]))->required(),
            Select::make('normal_balance')->label(__('accounting.normal_balance'))->options(['debit' => __('accounting.debit'), 'credit' => __('accounting.credit')])->default('debit')->required(),
        ])->action(fn (array $data) => app(CreateAccount::class)->handle(auth()->user(), $data))];
    }
}
