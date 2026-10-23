<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Modules\Core\Modules\ModuleManager;
use Closure;

final class ModuleRuntime
{
    /** @template T
     * @param  Closure(): T  $work
     * @return T|null
     */
    public function run(string $module, int $company, ?int $branch, Closure $work): mixed
    {
        if (! app(ModuleManager::class)->isEnabled($module, $company, $branch)) {
            return null;
        }

        return app(BusinessContext::class)->run($company, $branch, $work);
    }
}
