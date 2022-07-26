<?php

namespace ChiefTools\SDK\GraphQL\Directives;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;

class EnumValueDirective extends BaseDirective implements FieldMiddleware
{
    public function handleField(FieldValue $fieldValue, Closure $next): FieldValue
    {
        $previousResolver = $fieldValue->getResolver();

        $fieldValue->setResolver(function (mixed $root, array $args, GraphQLContext $context, ResolveInfo $info) use ($previousResolver) {
            return enum_value($previousResolver($root, $args, $context, $info));
        });

        return $next($fieldValue);
    }

    public static function definition(): string
    {
        return /* @lang GraphQL */ <<<'SDL'
        """
        Indicates that the field is an enum.
        """
        directive @enumValue on FIELD_DEFINITION
        SDL;
    }
}
