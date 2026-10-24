<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Modules\Accounting\Reports\ReportDefinition;
use App\Modules\Core\Modules\ModuleManager;
use App\Modules\Core\Modules\ModuleRegistry;
use InvalidArgumentException;

final class ReportRegistry
{
    /** @return array<string,ReportDefinition> */
    public function all(): array
    {
        $definitions = [];
        foreach (app(ModuleRegistry::class)->all() as $module) {
            if (! app(ModuleManager::class)->isEnabled($module->key())) {
                continue;
            }
            foreach ($module->reportDefinitions() as $class) {
                $definition = app($class);
                $definitions[$definition->key()] = $definition;
            }
        }

        return $definitions;
    }

    public function get(string $key): ReportDefinition
    {
        return $this->all()[$key] ?? throw new InvalidArgumentException('Unknown or disabled report definition.');
    }
}
