<?php

namespace ImranDevBd\AiCatalog;

use ImranDevBd\AiCatalog\Console\AgenticFeedCommand;
use ImranDevBd\AiCatalog\Console\AuditCatalogCommand;
use ImranDevBd\AiCatalog\Console\BulkCommand;
use ImranDevBd\AiCatalog\Console\ExportFeedCommand;
use ImranDevBd\AiCatalog\Console\GenerateCommand;
use ImranDevBd\AiCatalog\Console\InspectImageCommand;
use ImranDevBd\AiCatalog\Console\RefreshStaleCommand;
use ImranDevBd\AiCatalog\Console\RetryFailedCommand;
use ImranDevBd\AiCatalog\Http\Middleware\AuthorizeCatalogStudio;
use ImranDevBd\AiCatalog\Livewire\GenerateProduct;
use ImranDevBd\AiCatalog\Services\BulkImporter;
use ImranDevBd\AiCatalog\Services\CatalogAuditor;
use ImranDevBd\AiCatalog\Services\DppGenerator;
use ImranDevBd\AiCatalog\Services\ListingGenerator;
use ImranDevBd\AiCatalog\Services\ReturnReductionEngine;
use ImranDevBd\AiCatalog\Services\SemanticEmbedder;
use ImranDevBd\AiCatalog\Services\SocialCommerceGenerator;
use ImranDevBd\AiCatalog\Services\WorkflowService;
use ImranDevBd\AiCatalog\Support\AgenticFeedExporter;
use ImranDevBd\AiCatalog\Support\SpatialCommerceBuilder;
use ImranDevBd\AiCatalog\View\Components\GenerateButton;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AiCatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ai-catalog.php', 'ai-catalog');

        $this->app->singleton(ListingGenerator::class);
        $this->app->singleton(WorkflowService::class);
        $this->app->singleton(BulkImporter::class);
        $this->app->singleton(CatalogAuditor::class);
        $this->app->singleton(DppGenerator::class);
        $this->app->singleton(SocialCommerceGenerator::class);
        $this->app->singleton(ReturnReductionEngine::class);
        $this->app->singleton(SpatialCommerceBuilder::class);
        $this->app->singleton(SemanticEmbedder::class);
        $this->app->singleton(AgenticFeedExporter::class);
        $this->app->singleton(CatalogManager::class, fn ($app) => new CatalogManager(
            $app->make(ListingGenerator::class),
            $app->make(WorkflowService::class),
        ));
        $this->app->alias(CatalogManager::class, 'ai-catalog');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'ai-catalog');

        $this->publishes([
            __DIR__.'/../config/ai-catalog.php' => config_path('ai-catalog.php'),
        ], 'ai-catalog-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'ai-catalog-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/ai-catalog'),
        ], 'ai-catalog-views');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateCommand::class,
                BulkCommand::class,
                RetryFailedCommand::class,
                ExportFeedCommand::class,
                RefreshStaleCommand::class,
                InspectImageCommand::class,
                AuditCatalogCommand::class,
                AgenticFeedCommand::class,
            ]);
        }

        $this->registerRoutes();
        $this->registerStudio();
        $this->registerLivewire();
        $this->registerBlade();
    }

    protected function registerRoutes(): void
    {
        if (! config('ai-catalog.api.enabled', true)) {
            return;
        }

        $middleware = (array) config('ai-catalog.api.middleware', ['api']);
        $prefix = trim((string) config('ai-catalog.api.prefix', 'api/ai-catalog'), '/');

        Route::middleware($middleware)
            ->prefix($prefix)
            ->name('ai-catalog.')
            ->group(__DIR__.'/../routes/api.php');
    }

    protected function registerStudio(): void
    {
        if (! config('ai-catalog.studio.enabled', true)) {
            return;
        }

        $middleware = (array) config('ai-catalog.studio.middleware', ['web']);
        $middleware[] = AuthorizeCatalogStudio::class;
        $prefix = trim((string) config('ai-catalog.studio.prefix', 'ai-catalog'), '/');

        Route::middleware(array_values(array_unique($middleware)))
            ->prefix($prefix)
            ->name('ai-catalog.studio.')
            ->group(__DIR__.'/../routes/web.php');
    }

    protected function registerLivewire(): void
    {
        if (! class_exists(\Livewire\Livewire::class)) {
            return;
        }

        \Livewire\Livewire::component('ai-catalog.generate-product', GenerateProduct::class);
    }

    protected function registerBlade(): void
    {
        $this->loadViewComponentsAs('ai-catalog', [
            GenerateButton::class,
        ]);
    }
}
