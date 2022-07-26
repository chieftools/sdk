<?php

namespace ChiefTools\SDK\GraphQL;

use Illuminate\Http\Request;
use ChiefTools\SDK\Entities\User;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Context implements GraphQLContext
{
    private readonly ?User $user;

    public function __construct(
        private readonly Request $request
    ) {
        $this->user = $request->user();
    }

    public function user(): ?User
    {
        return $this->user;
    }

    public function request(): Request
    {
        return $this->request;
    }
}
