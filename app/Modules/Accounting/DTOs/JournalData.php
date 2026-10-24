<?php

declare(strict_types=1);

namespace App\Modules\Accounting\DTOs;

use App\Modules\Accounting\ValueObjects\Amount;
use Brick\Math\BigDecimal;

final readonly class JournalData
{
    /** @param list<JournalLineData> $lines */
    public function __construct(public string $entryDate, public string $currency, public string $exchangeRate, public string $narration,
        public array $lines, public string $sourceType, public string $sourceId, public int $version = 1) {}

    public function fingerprint(): string
    {
        $lines = array_map(fn (JournalLineData $line): array => ['account_id' => $line->accountId,
            'debit' => (string) (new Amount($line->debit))->value,
            'credit' => (string) (new Amount($line->credit))->value,
            'contact_id' => $line->contactId, 'cost_center' => $line->costCenter, 'narration' => $line->narration], $this->lines);

        return hash('sha256', json_encode(['date' => $this->entryDate, 'currency' => $this->currency, 'rate' => (string) BigDecimal::of($this->exchangeRate)->toScale(10),
            'narration' => $this->narration, 'lines' => $lines, 'source_type' => $this->sourceType, 'source_id' => $this->sourceId, 'version' => $this->version], JSON_THROW_ON_ERROR));
    }
}
