<?php

declare(strict_types=1);

namespace App\Modules\Core\Actions;

use App\Models\User;
use App\Modules\Core\Models\Branch;
use App\Modules\Core\Services\BusinessContext;
use Illuminate\Support\Facades\DB;

final class ExportBranches
{
    /** @param resource $stream */
    public function handle(User $actor, mixed $stream): void
    {
        abort_unless(DB::table('company_user')->where('company_id', app(BusinessContext::class)->companyId)->where('user_id', $actor->id)->where('is_owner', true)->exists(), 403);
        fputcsv($stream, ['name', 'code', 'timezone'], ',', '"', '');
        Branch::query()->orderBy('id')->chunkById(1000, function ($branches) use ($stream): void {
            foreach ($branches as $branch) {
                $cells = array_map(fn (string $value): string => preg_match('/^[=+@\-\t\r]/', $value) ? "'".$value : $value, [$branch->name, $branch->code, $branch->timezone]);
                fputcsv($stream, $cells, ',', '"', '');
            }
        });
    }
}
