<?php

namespace ChiefTools\SDK\GraphQL\Resolvers;

use ChiefTools\SDK\Entities\User;

class AuthenticatedUser extends AuthenticatedEntityResolver
{
    protected function execute(): ?User
    {
        return $this->resolveAuthenticatedEntity(User::class);
    }
}
