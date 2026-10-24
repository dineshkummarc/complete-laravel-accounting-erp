<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Reports;

final class BankBook extends FinancialReportDefinition
{
    public function key(): string
    {
        return 'bank-book';
    }
}
