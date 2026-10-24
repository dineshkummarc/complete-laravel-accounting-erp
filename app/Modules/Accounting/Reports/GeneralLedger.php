<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Reports;

final class GeneralLedger extends FinancialReportDefinition
{
    public function key(): string
    {
        return 'general-ledger';
    }
}
