<?php

namespace ChiefTools\SDK\GraphQL\Directives;

use ChiefTools\SDK\GraphQL\Context;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Exceptions\DefinitionException;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;
use ChiefTools\SDK\GraphQL\Exceptions\MissingScopeException;

class TokenScopesDirective extends BaseDirective implements FieldMiddleware
{
    public function handleField(FieldValue $fieldValue): void
    {
        $fieldValue->wrapResolver(
            fn (callable $resolver) => function (mixed $root, array $args, Context $context, ResolveInfo $resolveInfo) use ($resolver) {
                // Throw in case of an invalid schema definition to remind the developer
                $scopes = $this->directiveArgValue('scopes')
                          ?? throw new DefinitionException("Missing argument 'scopes' for directive '@tokenScope'.");

                if ($context->user() === null) {
                    return null;
                }

                $token = $context->token();

                // If there is no token, the user is authenticated using a different method and we can assume they have access
                if ($token !== null) {
                    foreach ($scopes as $scope) {
                        if ($token->cant($scope)) {
                            throw new MissingScopeException($resolveInfo->fieldName, $scopes, $token->scopes, allScopesRequired: true);
                        }
                    }
                }

                return $resolver($root, $args, $context, $resolveInfo);
            },
        );
    }

    public static function definition(): string
    {
        return /* @lang GraphQL */ <<<'SDL'
        """
        Limit field access to all of the given token scopes if authenticated with an access token.
        """
        directive @tokenScopes(
            """
            List of scopes that are required to access this field.
            """
            scopes: [String!]!
        ) on FIELD_DEFINITION
        SDL;
    }
}
