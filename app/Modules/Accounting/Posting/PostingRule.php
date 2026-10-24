<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Posting;

use App\Models\User;
use App\Modules\Accounting\DTOs\JournalData;

interface PostingRule
{
    public function module(): string;

    /** The rule must load and authorize the tenant-scoped source document. */
    public function build(User $actor, string $sourceId, int $version): JournalData;
}
