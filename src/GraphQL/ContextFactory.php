<?php

namespace ChiefTools\SDK\GraphQL;

use Illuminate\Http\Request;
use Nuwave\Lighthouse\Support\Contracts\CreatesContext;

class ContextFactory implements CreatesContext
{
    public function generate(?Request $request): Context
    {
        return new Context($request);
    }
}
