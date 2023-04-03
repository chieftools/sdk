<?php

namespace ChiefTools\SDK\GraphQL;

use Illuminate\Http\Request;
use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\Entities\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Context implements GraphQLContext
{
    private ?User $user;

    private readonly ?Team $team;

    public function __construct(
        private readonly ?Request $request,
    ) {
        $this->user = $request?->user();
        $this->team = $this->user?->team;
    }

    public function user(): ?User
    {
        return $this->user;
    }

    public function setUser(?Authenticatable $user): void
    {
        $this->user = $user;
    }

    public function team(): ?Team
    {
        return $this->team;
    }

    public function request(): ?Request
    {
        return $this->request;
    }
}
