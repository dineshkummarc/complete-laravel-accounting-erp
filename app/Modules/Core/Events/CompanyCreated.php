<?php

declare(strict_types=1);

namespace App\Modules\Core\Events;

final readonly class CompanyCreated
{
    public function __construct(public int $companyId, public int $ownerId) {}
}
