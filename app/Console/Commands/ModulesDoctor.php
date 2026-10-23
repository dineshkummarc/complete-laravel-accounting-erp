<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Core\Modules\ModuleRegistry;
use Illuminate\Console\Command;

final class ModulesDoctor extends Command
{
    protected $signature = 'modules:doctor';

    protected $description = 'Validate module manifests, dependency graph and English/Bangla names';

    public function handle(ModuleRegistry $registry): int
    {
        $all = $registry->all();
        $failures = [];
        $visited = [];
        $visiting = [];
        $visit = function (string $key) use (&$visit, &$visited, &$visiting, &$failures, $all): void {
            if (isset($visiting[$key])) {
                $failures[] = 'Dependency cycle at '.$key;

                return;
            }
            if (isset($visited[$key])) {
                return;
            }
            if (! isset($all[$key])) {
                $failures[] = 'Missing dependency '.$key;

                return;
            }
            $visiting[$key] = true;
            foreach ($all[$key]->dependsOn() as $dependency) {
                $visit($dependency);
            }
            unset($visiting[$key]);
            $visited[$key] = true;
        };
        foreach ($all as $key => $module) {
            if ($module->key() !== $key) {
                $failures[] = 'Manifest key mismatch: '.$key;
            }
            foreach (['en', 'bn'] as $locale) {
                if (! app('translator')->hasForLocale('modules.'.$key, $locale)) {
                    $failures[] = 'Missing translation '.$locale.':'.$key;
                }
            }
            $visit($key);
        }
        foreach ($failures as $failure) {
            $this->error($failure);
        }
        if ($failures !== []) {
            return self::FAILURE;
        }
        $this->info(count($all).' manifests passed graph and translation checks. Feature completeness is not certified.');

        return self::SUCCESS;
    }
}
