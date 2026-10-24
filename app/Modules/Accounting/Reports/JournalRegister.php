<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Reports;

final class JournalRegister extends FinancialReportDefinition
{
    public function key(): string
    {
        return 'journal-register';
    }
}
