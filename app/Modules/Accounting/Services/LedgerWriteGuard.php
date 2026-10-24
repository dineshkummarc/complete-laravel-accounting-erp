<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Services;

use Closure;
use Illuminate\Support\Facades\DB;
use LogicException;

final class LedgerWriteGuard
{
    private bool $writing = false;

    /** @var array<int, bool> */
    private array $drafts = [];

    /** @template T
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(Closure $callback): mixed
    {
        $previous = [$this->writing, $this->drafts];
        $this->writing = true;
        $this->drafts = [];
        try {
            return $callback();
        } finally {
            [$this->writing,$this->drafts] = $previous;
        }
    }

    public function assertWriting(): void
    {
        if (! $this->writing || DB::transactionLevel() === 0) {
            throw new LogicException('Ledger changes require a transactional accounting action.');
        }
    }

    public function assertDraft(int $entry): void
    {
        $this->assertWriting();
        $this->drafts[$entry] ??= DB::table('journal_entries')->where('id', $entry)->value('status') === 'draft';
        if (! $this->drafts[$entry]) {
            throw new LogicException('Posted journal lines are immutable.');
        }
    }
}
