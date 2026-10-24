<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Modules\Accounting\Actions;
use App\Modules\Accounting\DTOs\JournalInput;
use App\Modules\Accounting\Enums\JournalStatus;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Services\AccountChoices;
use App\Modules\Accounting\Services\LedgerAccess;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Services\BusinessContext;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

final class Journals extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static string $view = 'filament.pages.journals';

    public static function getNavigationLabel(): string
    {
        return __('accounting.journals');
    }

    public function getTitle(): string
    {
        return __('accounting.journals');
    }

    public static function canAccess(): bool
    {
        return auth()->check() && app(LedgerAccess::class)->allows(auth()->user(), 'accounting.view');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canAccess();
    }

    public function table(Table $table): Table
    {
        return $table->query(JournalEntry::query())->columns([
            TextColumn::make('number')->label(__('accounting.number'))->searchable()->placeholder(__('accounting.status_draft')),
            TextColumn::make('entry_date')->label(__('accounting.date'))->date()->sortable(),
            TextColumn::make('status')->label(__('accounting.status'))->formatStateUsing(fn (JournalStatus $state): string => $state->label())->badge(),
            TextColumn::make('currency')->label(__('accounting.currency')),
            TextColumn::make('narration')->label(__('accounting.narration'))->limit(60)->searchable(),
        ])->filters([SelectFilter::make('status')->label(__('accounting.status'))->options(collect(JournalStatus::cases())->mapWithKeys(fn (JournalStatus $status): array => [$status->value => $status->label()]))])
            ->actions([
                TableAction::make('view')->label(__('accounting.view'))->modalContent(fn (JournalEntry $record) => view('filament.accounting.journal-details', ['entry' => $record->load('lines.account')]))->modalSubmitAction(false),
                TableAction::make('submit')->label(__('accounting.submit'))->visible(fn (JournalEntry $record): bool => $record->status === JournalStatus::Draft && auth()->user()->can('update', $record))->action(fn (JournalEntry $record) => app(Actions\SubmitJournal::class)->handle(auth()->user(), $record->id)),
                TableAction::make('approve')->label(__('accounting.approve'))->requiresConfirmation()->visible(fn (JournalEntry $record): bool => auth()->user()->can('approve', $record))->action(fn (JournalEntry $record) => app(Actions\ApproveJournal::class)->handle(auth()->user(), $record->id)),
                TableAction::make('post')->label(__('accounting.post'))->requiresConfirmation()->visible(fn (JournalEntry $record): bool => auth()->user()->can('post', $record))->action(fn (JournalEntry $record) => app(Actions\PostJournal::class)->handle(auth()->user(), $record->id)),
                TableAction::make('reverse')->label(__('accounting.reverse'))->visible(fn (JournalEntry $record): bool => auth()->user()->can('reverse', $record))->form([
                    DatePicker::make('entry_date')->label(__('accounting.date'))->default(now()->toDateString())->required(), Textarea::make('reason')->label(__('business.reason'))->required(),
                ])->action(fn (JournalEntry $record, array $data) => app(Actions\ReverseJournal::class)->handle(auth()->user(), $record->id, $data['entry_date'], $data['reason'])),
            ])->defaultSort('id', 'desc')->paginated([25, 50, 100]);
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('create')->label(__('accounting.create_journal'))->visible(fn (): bool => auth()->user()->can('create', JournalEntry::class))->form([
            Hidden::make('source_id')->default(fn (): string => (string) Str::uuid()),
            DatePicker::make('entry_date')->label(__('accounting.date'))->default(now()->toDateString())->required(),
            TextInput::make('currency')->label(__('accounting.currency'))->default(fn (): string => Company::query()->findOrFail(app(BusinessContext::class)->companyId)->currency)->required()->length(3),
            TextInput::make('exchange_rate')->label(__('accounting.exchange_rate'))->default('1.0000000000')->required(),
            Textarea::make('narration')->label(__('accounting.narration'))->required(),
            Repeater::make('lines')->label(__('accounting.lines'))->schema([
                Select::make('account_id')->label(__('accounting.account'))->options(fn (): array => app(AccountChoices::class)->all())->searchable()->required(),
                TextInput::make('debit')->label(__('accounting.debit'))->default('0.0000')->required(),
                TextInput::make('credit')->label(__('accounting.credit'))->default('0.0000')->required(),
            ])->columns(3)->minItems(2)->defaultItems(2)->required(),
        ])->action(function (array $data): void {
            app(Actions\CreateJournal::class)->handle(auth()->user(), app(JournalInput::class)->handle($data));
            Notification::make()->title(__('business.saved'))->success()->send();
        })];
    }
}
