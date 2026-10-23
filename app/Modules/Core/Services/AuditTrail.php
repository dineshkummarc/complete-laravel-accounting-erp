<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use Illuminate\Support\Facades\DB;

final class AuditTrail
{
    /** @param array<string, mixed> $before
     * @param  array<string, mixed>  $after
     */
    public function record(int $company, ?int $branch, ?int $actor, string $action, string $type, string $id, array $before = [], array $after = []): void
    {
        DB::transaction(function () use ($company, $branch, $actor, $action, $type, $id, $before, $after): void {
            DB::table('companies')->where('id', $company)->lockForUpdate()->firstOrFail();
            $previous = DB::table('audit_events')->where('company_id', $company)->orderByDesc('id')->value('hash');
            $record = ['company_id' => $company, 'branch_id' => $branch, 'actor_id' => $actor, 'action' => $action,
                'subject_type' => $type, 'subject_id' => $id, 'before' => $this->canonical($before),
                'after' => $this->canonical($after), 'ip_address' => request()->ip(), 'previous_hash' => $previous, 'created_at' => now()->format('Y-m-d H:i:s')];
            DB::table('audit_events')->insert($record + ['hash' => $this->digest($record), 'created_at' => now(), 'updated_at' => now()]);
        });
    }

    public function verify(int $company): bool
    {
        $previous = null;
        foreach (DB::table('audit_events')->where('company_id', $company)->orderBy('id')->cursor() as $row) {
            $record = ['company_id' => $row->company_id, 'branch_id' => $row->branch_id, 'actor_id' => $row->actor_id,
                'action' => $row->action, 'subject_type' => $row->subject_type, 'subject_id' => $row->subject_id,
                'before' => $this->canonical(json_decode($row->before ?? '[]', true, flags: JSON_THROW_ON_ERROR)),
                'after' => $this->canonical(json_decode($row->after ?? '[]', true, flags: JSON_THROW_ON_ERROR)),
                'ip_address' => $row->ip_address, 'previous_hash' => $row->previous_hash, 'created_at' => $row->created_at];
            if ($row->previous_hash !== $previous || ! hash_equals($row->hash, $this->digest($record))) {
                return false;
            }
            $previous = $row->hash;
        }

        return true;
    }

    private function canonical(mixed $value): string
    {
        $sort = function (mixed $item) use (&$sort): mixed {
            if (! is_array($item)) {
                return $item;
            }
            if (! array_is_list($item)) {
                ksort($item);
            }

            return array_map($sort, $item);
        };

        return json_encode($sort($value), JSON_THROW_ON_ERROR);
    }

    /** @param array<string, mixed> $record */
    private function digest(array $record): string
    {
        $key = config('app.key');
        if (! is_string($key) || $key === '') {
            throw new \RuntimeException('Application key is required for audit integrity.');
        }

        return hash_hmac('sha256', json_encode($record, JSON_THROW_ON_ERROR), $key);
    }
}
