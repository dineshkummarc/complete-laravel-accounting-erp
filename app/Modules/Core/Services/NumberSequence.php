<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use LogicException;

final class NumberSequence
{
    public function next(string $type, int $year, string $prefix, string $pattern = '{PREFIX}-{BRANCH}-{YYYY}-{SEQ:6}'): string
    {
        if ($year < 1900 || $year > 9999 || ! preg_match('/^[a-z][a-z0-9-]{0,49}$/D', $type) || ! preg_match('/^[A-Z][A-Z0-9]{0,9}$/D', $prefix)) {
            throw new \InvalidArgumentException('Invalid document sequence.');
        }
        $context = app(BusinessContext::class);
        if (DB::transactionLevel() === 0 || $context->companyId === null || $context->branchId === null) {
            throw new LogicException('Document numbering requires an active business transaction and branch.');
        }
        $keys = ['company_id' => $context->companyId, 'branch_id' => $context->branchId, 'document_type' => $type, 'fiscal_year' => $year];
        DB::table('number_sequences')->insertOrIgnore($keys + ['last_number' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $row = DB::table('number_sequences')->where($keys)->lockForUpdate()->firstOrFail();
        $number = $row->last_number + 1;
        DB::table('number_sequences')->where('id', $row->id)->update(['last_number' => $number, 'updated_at' => now()]);
        $branch = DB::table('branches')->where('id', $context->branchId)->where('company_id', $context->companyId)->value('code');

        $format = strtr($pattern, ['{PREFIX}' => $prefix, '{BRANCH}' => $branch, '{YYYY}' => (string) $year]);

        return preg_replace_callback('/\{SEQ:(\d{1,2})\}/', fn (array $match): string => str_pad((string) $number, min((int) $match[1], 20), '0', STR_PAD_LEFT), $format) ?? throw new LogicException('Invalid number pattern.');
    }
}
