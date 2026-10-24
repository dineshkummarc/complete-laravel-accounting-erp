<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Enums;

enum JournalStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Posted = 'posted';
    case Reversed = 'reversed';

    public function label(): string
    {
        return __('accounting.status_'.$this->value);
    }
}
