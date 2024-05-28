<?php

namespace ChiefTools\SDK\GraphQL;

use Illuminate\Http\Request;
use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\Entities\User;
use Illuminate\Contracts\Auth\Authenticatable;
use ChiefTools\SDK\Auth\ChiefRemoteAccessToken;
use ChiefTools\SDK\Auth\AuthenticatesWithRemoteToken;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Context implements GraphQLContext
{
    private ?User $user;

    private ?Team $team;

    public function __construct(
        private readonly ?Request $request,
    ) {
        $this->setUser($request?->user());
    }

    public function user(): ?User
    {
        return $this->user;
    }

    public function setUser(?Authenticatable $user): void
    {
        if ($user instanceof User) {
            $this->user = $user;
            $this->team = $user->team;
        } elseif ($user instanceof Team) {
            $this->user = null;
            $this->team = $user;
        } else {
            $this->user = null;
            $this->team = null;
        }

        if ($this->user !== null) {
            $this->user->preventsLazyLoading = false;
        }

        if ($this->team !== null) {
            $this->team->preventsLazyLoading = false;
        }
    }

    public function team(): ?Team
    {
        return $this->team;
    }

    public function request(): ?Request
    {
        return $this->request;
    }

    public function token(): ?ChiefRemoteAccessToken
    {
        if (!$this->user instanceof AuthenticatesWithRemoteToken) {
            return null;
        }

        return $this->user->getChiefRemoteAccessToken();
    }
}
