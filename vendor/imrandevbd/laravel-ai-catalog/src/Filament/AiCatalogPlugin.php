<?php

namespace ImranDevBd\AiCatalog\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class AiCatalogPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'ai-catalog';
    }

    public function register(Panel $panel): void
    {
        if (! config('ai-catalog.filament.enabled', true)) {
            return;
        }

        $panel->resources([
            \ImranDevBd\AiCatalog\Filament\Resources\CatalogGenerationResource::class,
        ]);

        if (config('ai-catalog.filament.widgets', true)
            && class_exists(\ImranDevBd\AiCatalog\Filament\Widgets\CatalogCostWidget::class)
            && class_exists(\Filament\Widgets\StatsOverviewWidget::class)) {
            $panel->widgets([
                \ImranDevBd\AiCatalog\Filament\Widgets\CatalogCostWidget::class,
            ]);
        }
    }

    public function boot(Panel $panel): void {}
}
