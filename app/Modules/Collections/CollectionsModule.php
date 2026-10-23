<?php

declare(strict_types=1);

namespace App\Modules\Collections;

use App\Modules\Core\Modules\BaseModule;

final class CollectionsModule extends BaseModule
{
    public function key(): string
    {
        return 'collections';
    }

    public function dependsOn(): array
    {
        return ['sales'];
    }

    public function optionalIntegrations(): array
    {
        return ['messaging', 'ai-assistant'];
    }

    public function isCore(): bool
    {
        return false;
    }
}
