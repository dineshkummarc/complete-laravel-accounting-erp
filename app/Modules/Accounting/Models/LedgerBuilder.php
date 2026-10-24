<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Models;

use App\Modules\Accounting\Services\LedgerWriteGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** @template TModel of \Illuminate\Database\Eloquent\Model
 * @extends Builder<TModel>
 */
final class LedgerBuilder extends Builder
{
    /** @param \Illuminate\Database\Query\Builder $query
     * @param  TModel  $model
     */
    public function __construct($query, Model $model)
    {
        parent::__construct($query);
        $this->setModel($model);
    }

    public function update(array $values): int
    {
        app(LedgerWriteGuard::class)->assertWriting();

        return parent::update($values);
    }

    public function delete(): mixed
    {
        app(LedgerWriteGuard::class)->assertWriting();

        return parent::delete();
    }

    public function forceDelete(): mixed
    {
        app(LedgerWriteGuard::class)->assertWriting();

        return parent::forceDelete();
    }

    /** @param string $column
     * @param  int|float  $amount
     * @param  array<string,mixed>  $extra
     */
    public function increment($column, $amount = 1, array $extra = []): int
    {
        app(LedgerWriteGuard::class)->assertWriting();

        return parent::increment($column, $amount, $extra);
    }

    /** @param string $column
     * @param  int|float  $amount
     * @param  array<string,mixed>  $extra
     */
    public function decrement($column, $amount = 1, array $extra = []): int
    {
        app(LedgerWriteGuard::class)->assertWriting();

        return parent::decrement($column, $amount, $extra);
    }

    /** @param array<int|string,mixed> $values */
    public function insert(array $values): bool
    {
        app(LedgerWriteGuard::class)->assertWriting();

        return $this->toBase()->insert($values);
    }

    /** @param array<string,mixed> $values */
    public function insertGetId(array $values, ?string $sequence = null): int
    {
        app(LedgerWriteGuard::class)->assertWriting();

        return $this->toBase()->insertGetId($values, $sequence);
    }

    /** @param array<int|string,mixed> $values
     * @param  array<string>|string  $uniqueBy
     * @param  array<string>|null  $update
     */
    public function upsert(array $values, $uniqueBy, $update = null): int
    {
        app(LedgerWriteGuard::class)->assertWriting();

        return parent::upsert($values, $uniqueBy, $update);
    }

    /** @param array<int|string,mixed> $values */
    public function insertOrIgnore(array $values): int
    {
        app(LedgerWriteGuard::class)->assertWriting();

        return $this->toBase()->insertOrIgnore($values);
    }

    /** @param array<string,mixed> $attributes
     * @param  array<string,mixed>  $values
     */
    public function updateOrInsert(array $attributes, array $values = []): bool
    {
        app(LedgerWriteGuard::class)->assertWriting();

        return $this->toBase()->updateOrInsert($attributes, $values);
    }
}
