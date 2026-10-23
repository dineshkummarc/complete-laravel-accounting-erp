<?php

declare(strict_types=1);

return [
    'profiler' => [
        'enabled' => env('DRUTOGOTI_ENABLED', false),
        'sample_rate' => env('DRUTOGOTI_SAMPLE_RATE', 0.1),
        'exclude_paths' => ['drutogoti*', 'up', 'livewire/*', 'app/login'],
    ],
];
