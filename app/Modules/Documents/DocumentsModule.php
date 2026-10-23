<?php

declare(strict_types=1);

namespace App\Modules\Documents;

use App\Modules\Core\Modules\BaseModule;

final class DocumentsModule extends BaseModule
{
    public function key(): string
    {
        return 'documents';
    }

    public function dependsOn(): array
    {
        return [];
    }

    public function optionalIntegrations(): array
    {
        return [];
    }

    public function isCore(): bool
    {
        return false;
    }
}
