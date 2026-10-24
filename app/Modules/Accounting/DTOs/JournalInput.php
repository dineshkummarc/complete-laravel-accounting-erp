<?php

declare(strict_types=1);

namespace App\Modules\Accounting\DTOs;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class JournalInput
{
    /** @param array<string,mixed> $data */
    public function handle(array $data): JournalData
    {
        $v = Validator::make($data, ['entry_date' => 'required|date_format:Y-m-d', 'currency' => 'required|string|size:3', 'exchange_rate' => 'required|string', 'narration' => 'required|string|max:10000',
            'source_id' => 'nullable|string|max:100', 'lines' => 'required|array|min:2|max:500', 'lines.*.account_id' => 'required|integer|min:1', 'lines.*.debit' => 'required|string', 'lines.*.credit' => 'required|string', 'lines.*.narration' => 'nullable|string|max:10000'])->validate();
        try {
            $lines = array_map(fn (array $line): JournalLineData => new JournalLineData((int) $line['account_id'], $line['debit'], $line['credit'], narration: $line['narration'] ?? null), $v['lines']);

            return new JournalData($v['entry_date'], $v['currency'], $v['exchange_rate'], $v['narration'], $lines, 'manual', $v['source_id'] ?? (string) Str::uuid());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['lines' => $e->getMessage()]);
        }
    }
}
