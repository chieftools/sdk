<?php

namespace ChiefTools\SDK\GraphQL\Middleware;

use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Nuwave\Lighthouse\Http\Middleware\AttemptAuthentication as LighthouseAttemptAuthentication;

class AttemptAuthentication extends LighthouseAttemptAuthentication implements AuthenticatesRequests {}
