<?php

namespace ChiefTools\SDK\GraphQL\Directives;

use ChiefTools\SDK\Entities\User;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Nuwave\Lighthouse\Exceptions\AuthorizationException;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;

/**
 * Limit a user field to the authenticated user.
 *
 * Apps can expose other users, like the creator of a resource, whose private fields must not be visible to the
 * authenticated user or team.
 */
class ViewerOnlyDirective extends BaseDirective implements FieldMiddleware
{
    public function handleField(FieldValue $fieldValue): void
    {
        $fieldValue->wrapResolver(
            fn (callable $resolver) => function (mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo) use ($resolver) {
                $user = $context->user();

                if (!$root instanceof User || !$user instanceof User || !$user->is($root)) {
                    throw new AuthorizationException("The '{$this->nodeName()}' field is only available on the authenticated user.");
                }

                return $resolver($root, $args, $context, $resolveInfo);
            },
        );
    }

    public static function definition(): string
    {
        return /* @lang GraphQL */ <<<'SDL'
        """
        Limit the field to the authenticated user, other users return an authorization error.
        """
        directive @viewerOnly on FIELD_DEFINITION
        SDL;
    }
}
