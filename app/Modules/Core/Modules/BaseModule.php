<?php

declare(strict_types=1);

namespace App\Modules\Core\Modules;

use App\Modules\Core\Models\Company;
use Filament\Forms\Components\Component;
use Illuminate\Console\Scheduling\Schedule;

abstract class BaseModule
{
    abstract public function key(): string;

    /** @return list<string> */
    public function dependsOn(): array
    {
        return [];
    }

    /** @return list<string> */
    public function optionalIntegrations(): array
    {
        return [];
    }

    public function isCore(): bool
    {
        return false;
    }

    public function name(): string
    {
        return __('modules.'.$this->key());
    }

    public function version(): string
    {
        return '1.0.0';
    }

    /** @return list<string> */
    public function permissions(): array
    {
        return [$this->key().'.view', $this->key().'.manage'];
    }

    public function description(): string
    {
        return __('modules.'.$this->key());
    }

    public function group(): string
    {
        return $this->isCore() ? __('business.settings') : __('business.modules');
    }

    public function icon(): string
    {
        return 'heroicon-o-squares-2x2';
    }

    public function defaultEnabled(): bool
    {
        return $this->isCore();
    }

    /** @return list<string> */
    public function conflictsWith(): array
    {
        return [];
    }

    /** @return array<Component> */
    public function settingsSchema(): array
    {
        return [];
    }

    /** @return list<string> */
    public function requiredAccountMappings(): array
    {
        return [];
    }

    /** @return list<class-string> */
    public function dashboardWidgets(): array
    {
        return [];
    }

    /** @return list<class-string> */
    public function reportDefinitions(): array
    {
        return [];
    }

    /** @return list<class-string> */
    public function searchProviders(): array
    {
        return [];
    }

    /** @return list<string> */
    public function enableChecklist(Company $company): array
    {
        return array_values(array_filter($this->dependsOn(), fn (string $key): bool => ! app(ModuleManager::class)->isEnabled($key, $company->id)));
    }

    public function scheduledTasks(Schedule $schedule, Company $company): void {}

    public function onEnable(Company $company): void {}

    public function onDisable(Company $company): void {}

    public function seedCompanyDefaults(Company $company): void {}
}
