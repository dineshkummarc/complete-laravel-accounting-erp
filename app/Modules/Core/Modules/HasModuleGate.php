<?php

declare(strict_types=1);

namespace App\Modules\Core\Modules;

trait HasModuleGate
{
    public static function canAccess(): bool
    {
        return app(ModuleManager::class)->isEnabled(static::$module);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
