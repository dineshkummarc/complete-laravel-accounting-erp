<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Modules\Core\Modules\HasModuleGate;
use App\Modules\Core\Modules\ModuleManager;
use App\Modules\Core\Modules\ModuleRegistry;
use App\Modules\Core\Services\BusinessContext;
use App\Modules\Core\Services\CompanyAccess;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

final class Modules extends Page
{
    use HasModuleGate;

    protected static string $module = 'core';

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string $view = 'filament.pages.modules';

    public static function getNavigationLabel(): string
    {
        return __('business.modules');
    }

    public function getTitle(): string
    {
        return __('business.modules');
    }

    public function changeAction(): Action
    {
        return Action::make('change')->label(__('business.change_module'))->form([
            Select::make('key')->label(__('business.module'))->options(collect(app(ModuleRegistry::class)->all())->mapWithKeys(fn ($module): array => [$module->key() => $module->name()]))->required(),
            Toggle::make('enabled')->label(__('business.enabled'))->default(true),
            Toggle::make('branch_only')->label(__('business.branch_only'))->default(false),
            Textarea::make('reason')->label(__('business.reason'))->required(),
        ])->action(function (array $data): void {
            $context = app(BusinessContext::class);
            abort_unless($context->companyId !== null, 403);
            try {
                app(ModuleManager::class)->toggle(auth()->user(), $context->companyId, $data['key'], $data['enabled'], $data['reason'], $data['branch_only'] ? $context->branchId : null);
            } catch (ValidationException $exception) {
                $errors = [];
                foreach ($exception->errors() as $field => $messages) {
                    $errors[$this->getMountedActionForm()->getStatePath().'.'.($field === 'module' ? 'key' : $field)] = $messages;
                }
                throw ValidationException::withMessages($errors);
            }
            Notification::make()->title(__('business.saved'))->success()->send();
        })->visible(fn (): bool => auth()->check() && app(CompanyAccess::class)->isOwner(auth()->user()));
    }

    /** @return array<string, array{name: string, enabled: bool, dependencies: string}> */
    public function moduleCards(): array
    {
        $cards = [];
        foreach (app(ModuleRegistry::class)->all() as $key => $module) {
            $cards[$key] = ['name' => $module->name(), 'enabled' => app(ModuleManager::class)->isEnabled($key), 'dependencies' => implode(', ', $module->dependsOn())];
        }

        return $cards;
    }
}
