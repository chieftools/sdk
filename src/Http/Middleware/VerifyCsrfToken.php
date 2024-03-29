<?php

namespace ChiefTools\SDK\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{
    protected $except = [
        'api/*',
        'horizon/*',
        'webhooks/*',
    ];

    protected $addHttpCookie = false;
}
