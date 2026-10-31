<?php

namespace ChiefTools\SDK\GraphQL\Resolvers;

use ChiefTools\SDK\GraphQL\QueryResolver;
use Illuminate\Database\Eloquent\Collection;

/**
 * Resolves the teams of the authenticated user, limited to the team of the access token when it is limited to one team.
 *
 * The field is guarded by `@viewerOnly`, so the root is always the authenticated user. The teams are resolved from
 * the context user because that instance carries the access token, eager loading the relation would ignore its limit.
 *
 * @extends \ChiefTools\SDK\GraphQL\QueryResolver<\ChiefTools\SDK\Entities\User>
 */
class UserTeams extends QueryResolver
{
    /** @return \Illuminate\Database\Eloquent\Collection<int, \ChiefTools\SDK\Entities\Team> */
    protected function execute(): Collection
    {
        return $this->user()->accessibleTeams()->get();
    }
}
