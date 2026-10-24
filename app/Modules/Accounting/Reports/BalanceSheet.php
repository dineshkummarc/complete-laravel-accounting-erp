<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Reports;

final class BalanceSheet extends FinancialReportDefinition
{
    public function key(): string
    {
        return 'balance-sheet';
    }
}
