<?php

declare(strict_types=1);

namespace App\Modules\Core\Contracts;

use App\Models\User;

interface ImportDefinition
{
    public function module(): string;

    /** @return list<string> */
    public function columns(): array;

    /** @param array<string, string> $row */
    public function import(User $actor, array $row): void;
}
