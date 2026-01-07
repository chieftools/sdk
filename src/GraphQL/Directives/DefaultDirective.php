<?php

namespace ChiefTools\SDK\GraphQL\Directives;

use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;

class DefaultDirective extends BaseDirective implements FieldMiddleware
{
    public function handleField(FieldValue $fieldValue): void
    {
        $fieldValue->wrapResolver(
            fn (callable $previousResolver) => function (mixed $root, array $args, GraphQLContext $context, ResolveInfo $info) use ($previousResolver) {
                return $previousResolver($root, $args, $context, $info) ?? $this->directiveArgValue('value');
            },
        );
    }

    public static function definition(): string
    {
        return /* @lang GraphQL */ <<<'SDL'
        """
        If the fields returns `null` replace it with the value provided.
        """
        directive @default(value: EqValue) on FIELD_DEFINITION
        SDL;
    }
}
