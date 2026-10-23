<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Core\Modules\ModuleRegistry;
use Illuminate\Console\Command;

final class ModulesList extends Command
{
    protected $signature = 'modules:list';

    protected $description = 'List registered module manifests and required dependencies';

    public function handle(ModuleRegistry $registry): int
    {
        $this->table(['Key', 'Name', 'Dependencies', 'Core'], array_map(fn ($module): array => [$module->key(), $module->name(), implode(', ', $module->dependsOn()), $module->isCore() ? 'yes' : 'no'], array_values($registry->all())));

        return self::SUCCESS;
    }
}
