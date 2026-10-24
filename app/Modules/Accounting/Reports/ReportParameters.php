<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Reports;

final readonly class ReportParameters
{
    /** @param list<int> $branches */
    public function __construct(public string $from, public string $to, public array $branches, public ?int $accountId, public bool $includeOpening) {}
}
