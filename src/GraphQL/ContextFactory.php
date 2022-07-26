<?php

namespace ChiefTools\SDK\GraphQL;

use Illuminate\Http\Request;
use Nuwave\Lighthouse\Support\Contracts\CreatesContext;

class ContextFactory implements CreatesContext
{
    /**
     * Generate GraphQL context.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \ChiefTools\SDK\GraphQL\Context
     */
    public function generate(Request $request): Context
    {
        return new Context($request);
    }
}
