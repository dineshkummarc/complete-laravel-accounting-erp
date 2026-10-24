<?php

declare(strict_types=1);

namespace App\Modules\Accounting\DTOs;

use App\Modules\Accounting\ValueObjects\Amount;
use InvalidArgumentException;

final readonly class JournalLineData
{
    public function __construct(public int $accountId, public string $debit, public string $credit, public ?int $contactId = null, public ?string $costCenter = null, public ?string $narration = null)
    {
        $debit = (new Amount($debit))->value;
        $credit = (new Amount($credit))->value;
        if ($accountId < 1 || $debit->isZero() === $credit->isZero()) {
            throw new InvalidArgumentException('Each line requires exactly one positive debit or credit.');
        }
    }
}
