<?php

declare(strict_types=1);

namespace App\Providers;

use App\Integrations\Ai\PackageAiGateway;
use App\Integrations\Attendance\PackageAttendanceGateway;
use App\Integrations\Catalog\PackageCatalogService;
use App\Integrations\Console\PackageAdminPanelAdapter;
use App\Integrations\Contracts;
use App\Integrations\Pdf\PackagePdfRenderer;
use App\Integrations\Speed\PackageDrutogotiAdapter;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use ImranDevBd\AiHub\Facades\AIHub;

final class IntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Contracts\DrutogotiAdapter::class, PackageDrutogotiAdapter::class);
        $this->app->bind(Contracts\AdminPanelAdapter::class, PackageAdminPanelAdapter::class);
        $this->app->bind(Contracts\PdfRenderer::class, PackagePdfRenderer::class);
        $this->app->bind(Contracts\AiGateway::class, PackageAiGateway::class);
        $this->app->bind(Contracts\CatalogService::class, PackageCatalogService::class);
        $this->app->bind(Contracts\AttendanceGateway::class, PackageAttendanceGateway::class);
        config(['ai-hub.settings.middleware' => ['web', 'auth', 'can:platform.console'], 'ai-hub.filament.enabled' => false]);
        // Device ingestion stays closed until company/device authentication is implemented in Phase 7.
        config(['attendance-hub.adms.enabled' => false]);
    }

    public function boot(): void
    {
        setPermissionsTeamId(0);
        AIHub::auth(fn ($request): bool => $request->user()?->can('platform.console') ?? false);
        Gate::define('viewAiCatalog', fn (User $user): bool => $user->can('platform.console'));
    }
}
