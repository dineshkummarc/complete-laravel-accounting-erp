<?php

declare(strict_types=1);

namespace App\Modules\Core\Modules;

use InvalidArgumentException;

final class ModuleRegistry
{
    /** @return array<string, BaseModule> */
    public function all(): array
    {
        return array_map(fn (string $class): BaseModule => app($class), config('modules'));
    }

    public function get(string $key): BaseModule
    {
        return $this->all()[$key] ?? throw new InvalidArgumentException('Unknown module: '.$key);
    }
}
