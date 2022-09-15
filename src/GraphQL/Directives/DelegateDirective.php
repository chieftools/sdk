<?php

namespace ChiefTools\SDK\GraphQL\Directives;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;

class DelegateDirective extends BaseDirective implements FieldMiddleware
{
    public function handleField(FieldValue $fieldValue, Closure $next): FieldValue
    {
        $resolver = $fieldValue->getResolver();

        $field = $this->directiveArgValue('field');

        $fieldValue->setResolver(
            static function ($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo) use ($resolver, $field) {
                return $resolver($root->{$field}, $args, $context, $resolveInfo);
            },
        );

        return $next($fieldValue);
    }

    public static function definition(): string
    {
        return /* @lang GraphQL */ <<<'SDL'
        """
        Delegate the resolving to a child of the current root essentially pretending that the child is the root for this field.
        """
        directive @delegate(field: String!) on FIELD_DEFINITION
        SDL;
    }
}
