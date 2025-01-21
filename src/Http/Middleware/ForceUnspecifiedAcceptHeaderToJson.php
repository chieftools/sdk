<?php

namespace ChiefTools\SDK\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\AcceptHeader;

class ForceUnspecifiedAcceptHeaderToJson
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->acceptsAnyContentType($request)) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }

    private function acceptsAnyContentType(Request $request): bool
    {
        $acceptable = $this->getAcceptableContentTypes($request);

        if (count($acceptable) === 0) {
            return true;
        }

        return isset($acceptable[0]) && ($acceptable[0] === '*/*' || $acceptable[0] === '*');
    }

    private function getAcceptableContentTypes(Request $request): array
    {
        return array_map('strval', array_keys(AcceptHeader::fromString($request->header('Accept'))->all()));
    }
}
