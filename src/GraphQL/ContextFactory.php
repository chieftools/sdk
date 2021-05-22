<?php

namespace IronGate\Chief\GraphQL;

use Illuminate\Http\Request;
use Nuwave\Lighthouse\Support\Contracts\CreatesContext;

class ContextFactory implements CreatesContext
{
    /**
     * Generate GraphQL context.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \IronGate\Chief\GraphQL\Context
     */
    public function generate(Request $request): Context
    {
        return new Context($request);
    }
}
