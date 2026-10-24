<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Reports;

final class IncomeStatement extends FinancialReportDefinition
{
    public function key(): string
    {
        return 'income-statement';
    }
}
