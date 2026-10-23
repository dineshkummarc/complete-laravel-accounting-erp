<?php

declare(strict_types=1);

namespace App\Modules\Core\Events;

final readonly class ModuleStateChanged
{
    public function __construct(public int $companyId, public ?int $branchId, public string $key, public bool $enabled, public int $actorId, public string $reason) {}
}
