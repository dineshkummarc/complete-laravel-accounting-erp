<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Reports;

final class TrialBalance extends FinancialReportDefinition
{
    public function key(): string
    {
        return 'trial-balance';
    }
}
