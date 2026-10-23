<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\BusinessServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\IntegrationServiceProvider;

return [
    AppServiceProvider::class,
    IntegrationServiceProvider::class,
    BusinessServiceProvider::class,
    AdminPanelProvider::class,
];
