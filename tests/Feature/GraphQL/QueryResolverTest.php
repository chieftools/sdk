<?php

use Illuminate\Http\Request;
use ChiefTools\SDK\GraphQL\Context;
use Illuminate\Support\Facades\Gate;
use GraphQL\Type\Definition\ResolveInfo;
use ChiefTools\SDK\GraphQL\QueryResolver;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nuwave\Lighthouse\Exceptions\AuthorizationException;

uses(LazilyRefreshDatabase::class);

it('names the field path when the user is not authorized', function () {
    $user = createTeamContextUser();
    createTeamContextTeam('amber-grove', $user);

    Gate::define('viewSecrets', static fn (): bool => false);

    $request = Request::create('/api/graphql');
    $request->setUserResolver(static fn () => $user);

    $resolveInfo       = (new ReflectionClass(ResolveInfo::class))->newInstanceWithoutConstructor();
    $resolveInfo->path = ['team', 'secrets'];

    $resolver = new class extends QueryResolver
    {
        protected function execute(): mixed
        {
            $this->authorize('viewSecrets');

            return null;
        }
    };

    $resolver(null, [], new Context($request), $resolveInfo);
})->throws(AuthorizationException::class, 'You are not authorized to access `team.secrets`.');
