<?php

namespace ChiefTools\SDK\GraphQL;

use Illuminate\Auth\Access\Response;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Exceptions\AuthorizationException;
use Illuminate\Auth\Access\AuthorizationException as IlluminateAuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests as IlluminateAuthorizesRequests;

trait AuthorizesRequests
{
    use IlluminateAuthorizesRequests;

    public function authorizeAction(ResolveInfo $info, $ability, $arguments = []): ?Response
    {
        // Nothing to authorize
        if ($arguments === null) {
            return null;
        }

        try {
            return $this->authorize($ability, $arguments);
        } catch (IlluminateAuthorizationException) {
            $fieldPath = implode('.', $info->path);

            throw new AuthorizationException("You are not authorized to access `{$fieldPath}`.");
        }
    }
}
