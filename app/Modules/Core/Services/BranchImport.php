<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Models\User;
use App\Modules\Core\Actions\CreateBranch;
use App\Modules\Core\Contracts\ImportDefinition;

final class BranchImport implements ImportDefinition
{
    public function module(): string
    {
        return 'core';
    }

    public function columns(): array
    {
        return ['name', 'code', 'timezone'];
    }

    public function import(User $actor, array $row): void
    {
        app(CreateBranch::class)->handle($actor, $row);
    }
}
