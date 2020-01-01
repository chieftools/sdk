<?php

namespace IronGate\Chief\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        // Make sure we're dealing with what we think we are dealing with
        if (!($response instanceof Response)) {
            // Make sure there are no empty headers being set
            $headers = array_filter(config('chief.response.securityheaders', []));

            foreach ($headers as $header => $value) {
                // Set the headers but do not replace them if already set
                if ($response->headers->has($header)) {
                    $response->headers->set($header, $value);
                }
            }
        }

        return $response;
    }
}
