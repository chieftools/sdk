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
     * @return \Nuwave\Lighthouse\Support\Contracts\GraphQLContext
     */
    public function generate(Request $request)
    {
        return new Context($request);
    }
}
