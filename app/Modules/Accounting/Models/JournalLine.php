<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Models;

use App\Modules\Accounting\Services\LedgerWriteGuard;
use App\Modules\Core\Models\BusinessModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property string $debit
 * @property string $credit
 * @property string $base_debit
 * @property string $base_credit
 */
final class JournalLine extends BusinessModel
{
    protected static bool $branchScoped = true;

    /** @return array{entry_date:'immutable_date',debit:'decimal:4',credit:'decimal:4',base_debit:'decimal:4',base_credit:'decimal:4',is_rounding:'boolean'} */
    protected function casts(): array
    {
        return ['entry_date' => 'immutable_date', 'debit' => 'decimal:4', 'credit' => 'decimal:4', 'base_debit' => 'decimal:4', 'base_credit' => 'decimal:4', 'is_rounding' => 'boolean'];
    }

    /** @return BelongsTo<Account,$this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** @return BelongsTo<JournalEntry,$this> */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    /** @return LedgerBuilder<$this> */
    public function newEloquentBuilder($query): LedgerBuilder
    {
        return new LedgerBuilder($query, $this);
    }

    protected static function booted(): void
    {
        parent::booted();
        foreach (['creating', 'updating', 'deleting'] as $event) {
            self::$event(fn (self $line) => app(LedgerWriteGuard::class)->assertDraft($line->journal_entry_id));
        }
    }
}
