<?php

namespace ChiefTools\SDK\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use function Sentry\metrics;

class SentryMetricsFlusher
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        metrics()->flush();
    }
}
