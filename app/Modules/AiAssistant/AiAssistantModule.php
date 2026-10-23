<?php

declare(strict_types=1);

namespace App\Modules\AiAssistant;

use App\Modules\Core\Modules\BaseModule;

final class AiAssistantModule extends BaseModule
{
    public function key(): string
    {
        return 'ai-assistant';
    }

    public function dependsOn(): array
    {
        return [];
    }

    public function optionalIntegrations(): array
    {
        return ['expenses', 'purchases', 'banking', 'collections'];
    }

    public function isCore(): bool
    {
        return false;
    }
}
