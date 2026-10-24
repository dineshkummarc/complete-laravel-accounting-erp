<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Modules\Accounting\Services\AccountChoices;
use App\Modules\Accounting\Services\LedgerAccess;
use App\Modules\Core\Services\ReportRegistry;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class FinancialReports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.financial-reports';

    public string $report = 'trial-balance';

    public ?int $accountId = null;

    public string $from = '';

    public string $to = '';

    /** @var array<string,mixed> */
    public array $result = [];

    public static function getNavigationLabel(): string
    {
        return __('reports.title');
    }

    public function getTitle(): string
    {
        return __('reports.title');
    }

    public static function canAccess(): bool
    {
        return auth()->check() && app(LedgerAccess::class)->allows(auth()->user(), 'accounting.report.view', false);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canAccess();
    }

    public function mount(): void
    {
        $this->from = now()->startOfYear()->toDateString();
        $this->to = now()->toDateString();
    }

    /** @return array<string,string> */
    public function options(): array
    {
        return array_map(fn ($definition): string => $definition->title(), app(ReportRegistry::class)->all());
    }

    /** @return array<int,string> */
    public function accounts(): array
    {
        return app(AccountChoices::class)->all();
    }

    public function generate(): void
    {
        abort_unless(self::canAccess(), 403);
        $r = app(ReportRegistry::class)->get($this->report)->query(auth()->user(), ['from' => $this->from, 'to' => $this->to, 'account_id' => $this->accountId]);
        $this->result = ['columns' => $r->columns, 'rows' => $r->rows, 'totals' => $r->totals, 'metadata' => $r->metadata];
    }

    public function download(): StreamedResponse
    {
        abort_unless(self::canAccess(), 403);
        $r = app(ReportRegistry::class)->get($this->report)->query(auth()->user(), ['from' => $this->from, 'to' => $this->to, 'account_id' => $this->accountId], null);

        return response()->streamDownload(function () use ($r): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fputcsv($out, array_values($r->columns), ',', '"', '');
            foreach ($r->rows as $row) {
                $cells = [];
                foreach (array_keys($r->columns) as $key) {
                    $cell = (string) ($row[$key] ?? '');
                    $cells[] = preg_match('/^[=+@\-\t\r]/', $cell) ? "'".$cell : $cell;
                }
                fputcsv($out, $cells, ',', '"', '');
            }
            fclose($out);
        }, $r->key.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
