<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Reports;

final readonly class ReportResult
{
    /** @param array<string,string> $columns
     * @param  list<array<string,string|int>>  $rows
     * @param  array<string,string>  $totals
     * @param  array<string,mixed>  $metadata
     */
    public function __construct(public string $key, public array $columns, public array $rows, public array $totals, public array $metadata = []) {}
}
