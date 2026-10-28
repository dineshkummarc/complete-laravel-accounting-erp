<?php

namespace ImranDevBd\AiCatalog\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeCatalogStudio
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local', 'testing')) {
            return $next($request);
        }

        $gate = (string) config('ai-catalog.studio.gate', 'viewAiCatalog');
        if ($gate && Gate::has($gate)) {
            if (Gate::forUser($request->user())->allows($gate)) {
                return $next($request);
            }
            abort(403, 'Unauthorized access to Laravel AI Catalog Studio.');
        }

        if ($request->user()) {
            return $next($request);
        }

        abort(403, 'Unauthorized access to Laravel AI Catalog Studio.');
    }
}
