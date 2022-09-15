<?php

namespace ChiefTools\SDK\GraphQL;

use Illuminate\Http\Request;
use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\Entities\User;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Context implements GraphQLContext
{
    private readonly ?User $user;
    private readonly ?Team $team;

    public function __construct(
        private readonly Request $request,
    ) {
        $this->user = $request->user();
        $this->team = $this->user?->team;
    }

    public function user(): ?User
    {
        return $this->user;
    }

    public function team(): ?Team
    {
        return $this->team;
    }

    public function request(): Request
    {
        return $this->request;
    }
}
