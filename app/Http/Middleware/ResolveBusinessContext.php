<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Core\Services\BusinessContext;
use App\Modules\Core\Services\CompanyAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveBusinessContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $context = app(BusinessContext::class);
        setPermissionsTeamId(0);
        $context->companyId = null;
        $context->branchId = null;
        if ($user?->current_company_id !== null && ! $request->is('admin', 'admin/*', 'drutogoti*', 'ai-hub*', 'ai-catalog*')) {
            if (app(CompanyAccess::class)->isMember($user, $user->current_company_id)) {
                $branch = $user->current_branch_id;
                if ($branch !== null && ! $context->canAccessBranch($user, $user->current_company_id, $branch)) {
                    $branch = null;
                }
                $context->select($user, $user->current_company_id, $branch);
            } else {
                $user->forceFill(['current_company_id' => null, 'current_branch_id' => null])->save();
            }
            app()->setLocale($user->locale);
        }

        return $next($request);
    }
}
