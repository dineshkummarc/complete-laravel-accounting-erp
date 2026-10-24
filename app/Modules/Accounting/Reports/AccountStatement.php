<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Reports;

final class AccountStatement extends FinancialReportDefinition
{
    public function key(): string
    {
        return 'account-statement';
    }
}
