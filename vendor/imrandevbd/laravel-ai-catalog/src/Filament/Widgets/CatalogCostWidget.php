<?php

namespace ImranDevBd\AiCatalog\Filament\Widgets;

use ImranDevBd\AiCatalog\Support\CostTracker;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CatalogCostWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $summary = app(CostTracker::class)->summary(null, 30);

        return [
            Stat::make('Catalog AI (30d)', '$'.number_format($summary['cost_usd'], 4))
                ->description($summary['requests'].' generations · '.$summary['tokens'].' tokens'),
            Stat::make('Failed generations', (string) $summary['failed'])
                ->color($summary['failed'] > 0 ? 'danger' : 'success'),
        ];
    }

    public static function canView(): bool
    {
        return (bool) config('ai-catalog.filament.widgets', true)
            && class_exists(StatsOverviewWidget::class);
    }
}
