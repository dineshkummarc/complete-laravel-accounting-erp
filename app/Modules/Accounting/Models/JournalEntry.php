<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Models;

use App\Modules\Accounting\Enums\JournalStatus;
use App\Modules\Accounting\Services\LedgerWriteGuard;
use App\Modules\Core\Models\BusinessModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/** @property string|null $source_hash
 * @property string $exchange_rate
 */
final class JournalEntry extends BusinessModel
{
    protected static bool $branchScoped = true;

    /** @return array{status: class-string<JournalStatus>, entry_date: 'immutable_date', exchange_rate: 'decimal:10', posted_at: 'immutable_datetime'} */
    protected function casts(): array
    {
        return ['status' => JournalStatus::class, 'entry_date' => 'immutable_date', 'exchange_rate' => 'decimal:10', 'posted_at' => 'immutable_datetime'];
    }

    /** @return HasMany<JournalLine,$this> */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    /** @return LedgerBuilder<$this> */
    public function newEloquentBuilder($query): LedgerBuilder
    {
        return new LedgerBuilder($query, $this);
    }

    protected static function booted(): void
    {
        parent::booted();
        self::creating(fn () => app(LedgerWriteGuard::class)->assertWriting());
        self::updating(function (self $entry): void {
            app(LedgerWriteGuard::class)->assertWriting();
            if (in_array($entry->getRawOriginal('status'), ['posted', 'reversed'], true)) {
                $changes = array_diff(array_keys($entry->getDirty()), ['status', 'reversal_entry_id', 'updated_at', 'updated_by']);
                if ($entry->getRawOriginal('status') !== 'posted' || $entry->status !== JournalStatus::Reversed || $changes !== [] || ! $entry->reversal_entry_id) {
                    throw new LogicException('Posted journal entries are immutable.');
                }
            }
        });
        self::deleting(fn () => throw new LogicException('Journal entries cannot be deleted.'));
    }
}
