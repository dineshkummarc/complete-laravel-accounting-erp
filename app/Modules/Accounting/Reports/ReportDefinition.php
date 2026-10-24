<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Reports;

use App\Models\User;

interface ReportDefinition
{
    public function key(): string;

    public function title(): string;

    public function module(): string;

    /** @return array<string,mixed> */
    public function parameters(): array;

    /** @return array<string,string> */
    public function columns(): array;

    /** @param array<string,mixed> $parameters */
    public function query(User $actor, array $parameters, ?int $limit = 100): ReportResult;
}
