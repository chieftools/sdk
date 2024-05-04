<?php

namespace ChiefTools\SDK\GraphQL\Resolvers;

use ChiefTools\SDK\GraphQL\QueryResolver;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

/**
 * @extends \ChiefTools\SDK\GraphQL\QueryResolver<null>
 */
abstract class AuthenticatedEntityResolver extends QueryResolver
{
    public function __construct(
        protected readonly AuthFactory $authFactory,
    ) {}

    /**
     * @template TEntity of \Illuminate\Contracts\Auth\Authenticatable
     *
     * @param class-string<TEntity> $entityClass
     *
     * @return TEntity|null
     */
    protected function resolveAuthenticatedEntity(string $entityClass)
    {
        $guards = config('lighthouse.guards') ?? [config('auth.defaults.guard')];

        foreach ($guards as $guard) {
            $user = $this->authFactory->guard($guard)->user();

            if ($user instanceof $entityClass) {
                return $user;
            }
        }

        return null;
    }
}
