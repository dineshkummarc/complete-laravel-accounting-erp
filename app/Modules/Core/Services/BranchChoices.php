<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\Branch;
use Illuminate\Support\Facades\DB;

final class BranchChoices
{
    /** @return array<int, string> */
    public function all(): array
    {
        $context = app(BusinessContext::class);
        $actor = auth()->user();
        if ($context->companyId === null || $actor === null) {
            return [];
        }
        $owner = app(CompanyAccess::class)->isOwner($actor);
        $query = Branch::query()->where('is_active', true);
        if (! $owner) {
            $query->whereIn('id', DB::table('branch_user')->where('user_id', $actor->id)->select('branch_id'));
        }

        return $query->pluck('name', 'id')->all();
    }
}
