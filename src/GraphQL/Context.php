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
            $this->team = $user->currentTeam($this->request);
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

    /** Determine if the request is authenticated as a user or as a team. */
    public function isAuthenticated(): bool
    {
        return $this->user !== null || $this->team !== null;
    }

    /**
     * The access token of the authenticated user, or of the authenticated team when using a team token.
     *
     * The team is only consulted when there is no user, a user's current team never carries the user's token.
     */
    public function token(): ?ChiefRemoteAccessToken
    {
        $authenticated = $this->user ?? $this->team;

        if (!$authenticated instanceof AuthenticatesWithRemoteToken) {
            return null;
        }

        return $authenticated->getChiefRemoteAccessToken();
    }
}
