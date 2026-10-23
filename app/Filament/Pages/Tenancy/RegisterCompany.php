<?php

declare(strict_types=1);

namespace App\Filament\Pages\Tenancy;

use App\Modules\Core\Actions\CreateCompany;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Database\Eloquent\Model;

final class RegisterCompany extends RegisterTenant
{
    public static function getLabel(): string
    {
        return __('business.create_company');
    }

    public static function canView(): bool
    {
        return auth()->check();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('preset')->label(__('business.preset'))->options(collect(array_keys(config('presets')))->mapWithKeys(fn (string $key): array => [$key => __('business.preset_'.$key)]))->default('custom')->required(),
            TextInput::make('name')->label(__('business.company_name'))->required()->maxLength(255),
            TextInput::make('currency')->label(__('business.currency'))->default('BDT')->length(3)->required(),
            Select::make('locale')->label(__('business.language'))->options(['en' => 'English', 'bn' => 'বাংলা'])->default('en')->required(),
            Select::make('localization_pack')->label(__('business.localization_pack'))->options(['generic' => __('business.generic'), 'bd' => __('business.bangladesh')])->default('generic')->required(),
            Select::make('timezone')->label(__('business.timezone'))->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))->searchable()->default('Asia/Dhaka')->required(),
        ]);
    }

    protected function handleRegistration(array $data): Model
    {
        return app(CreateCompany::class)->handle(auth()->user(), $data);
    }
}
