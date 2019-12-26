<?php

namespace IronGate\Integration\GraphQL;

use Illuminate\Http\Request;
use IronGate\Integration\Entities\User;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Context implements GraphQLContext
{
    /**
     * An instance of the incoming HTTP request.
     *
     * @var \Illuminate\Http\Request
     */
    private $request;

    /**
     * An instance of the currently authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    private $user;

    /**
     * GraphQLContext.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->user    = $request->user();
    }

    /**
     * Get instance of authenticated user.
     *
     * May be null since some fields may be accessible without authentication.
     *
     * @return \IronGate\Integration\Entities\User|null
     */
    public function user(): ?User
    {
        return $this->user;
    }

    /**
     * Get instance of request.
     *
     * @return \Illuminate\Http\Request
     */
    public function request(): Request
    {
        return $this->request;
    }
}
