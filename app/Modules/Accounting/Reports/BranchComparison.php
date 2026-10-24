<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Reports;

final class BranchComparison extends FinancialReportDefinition
{
    public function key(): string
    {
        return 'branch-comparison';
    }
}
