<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Core\Models\Company;
use Brick\Math\BigDecimal;

final class ApprovalRules
{
    public function required(JournalEntry $entry): bool
    {
        $company = Company::query()->findOrFail($entry->company_id);
        $settings = json_decode($company->getRawOriginal('accounting_settings') ?? '{}', true, flags: JSON_THROW_ON_ERROR);
        if (! ($settings['maker_checker'] ?? false)) {
            return false;
        }
        $total = BigDecimal::zero();
        foreach ($entry->lines as $line) {
            $total = $total->plus($line->base_debit);
        }

        return $total->isGreaterThanOrEqualTo($settings['approval_threshold'] ?? '0.0000');
    }
}
