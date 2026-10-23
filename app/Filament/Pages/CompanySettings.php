<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Modules\Core\Actions\UpdateCompany;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Services\BusinessContext;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/** @property Form $form */
final class CompanySettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.company-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('business.company_settings');
    }

    public function getTitle(): string
    {
        return __('business.company_settings');
    }

    public static function canAccess(): bool
    {
        return Team::canAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canAccess();
    }

    public function mount(): void
    {
        abort_unless(self::canAccess(), 403);
        $company = Company::query()->findOrFail(app(BusinessContext::class)->companyId);
        $this->form->fill($company->only(['name', 'locale', 'timezone', 'fiscal_year_start_month']));
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->schema([
            TextInput::make('name')->label(__('business.company_name'))->required()->maxLength(255),
            Select::make('locale')->label(__('business.language'))->options(['en' => 'English', 'bn' => 'বাংলা'])->required(),
            Select::make('timezone')->label(__('business.timezone'))->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))->searchable()->required(),
            Select::make('fiscal_year_start_month')->label(__('business.fiscal_month'))->options(array_combine(range(1, 12), array_map(strval(...), range(1, 12))))->required(),
        ]);
    }

    public function save(): void
    {
        abort_unless(self::canAccess(), 403);
        app(UpdateCompany::class)->handle(auth()->user(), Company::query()->findOrFail(app(BusinessContext::class)->companyId), $this->form->getState());
        Notification::make()->title(__('business.saved'))->success()->send();
    }
}
