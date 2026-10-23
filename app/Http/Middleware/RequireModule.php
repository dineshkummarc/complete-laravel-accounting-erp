<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Core\Modules\ModuleManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireModule
{
    public function handle(Request $request, Closure $next, string $key): Response
    {
        abort_unless(app(ModuleManager::class)->isEnabled($key), $request->expectsJson() ? 403 : 404);

        return $next($request);
    }
}
