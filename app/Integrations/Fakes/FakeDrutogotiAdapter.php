<?php

declare(strict_types=1);

namespace App\Integrations\Fakes;

use App\Integrations\Contracts\DrutogotiAdapter;

final class FakeDrutogotiAdapter implements DrutogotiAdapter
{
    public function summary(): array
    {
        return ['enabled' => false, 'requests' => 0, 'slow_queries' => 0, 'open_suggestions' => 0];
    }
}
