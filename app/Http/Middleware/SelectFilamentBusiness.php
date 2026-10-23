<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Core\Models\Company;
use App\Modules\Core\Services\BusinessContext;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

final class SelectFilamentBusiness
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();
        $user = $request->user();
        if ($tenant instanceof Company && $user) {
            $context = app(BusinessContext::class);
            $branch = $user->current_branch_id;
            if ($branch === null || ! $context->canAccessBranch($user, $tenant->id, $branch)) {
                $branch = DB::table('branches')->where('company_id', $tenant->id)->where('is_active', true)->whereNull('deleted_at')->orderBy('id')->pluck('id')
                    ->first(fn (int $id): bool => $context->canAccessBranch($user, $tenant->id, $id));
            }
            $context->select($user, $tenant->id, $branch);
            $user->forceFill(['current_company_id' => $tenant->id, 'current_branch_id' => $branch])->save();
        }

        return $next($request);
    }
}
