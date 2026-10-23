<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Integrations\Speed\RequestCapture;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CapturePerformance
{
    public function __construct(private readonly RequestCapture $capture) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->capture->begin($request);
        $response = $next($request);
        $this->capture->finish($request, $response);

        return $response;
    }
}
