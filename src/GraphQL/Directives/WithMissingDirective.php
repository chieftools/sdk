<?php

namespace IronGate\Chief\GraphQL\Directives;

use Closure;
use GraphQL\Deferred;
use Illuminate\Database\Eloquent\Model;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Execution\DataLoader\BatchLoader;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;
use Nuwave\Lighthouse\Support\Contracts\DefinedDirective;
use Nuwave\Lighthouse\Schema\Directives\RelationDirective;
use IronGate\Chief\GraphQL\Support\MissingRelationBatchLoader;

class WithMissingDirective extends RelationDirective implements FieldMiddleware, DefinedDirective
{
    public function handleField(FieldValue $fieldValue, Closure $next): FieldValue
    {
        $resolver = $fieldValue->getResolver();

        return $next(
            $fieldValue->setResolver(function (Model $parent, array $args, GraphQLContext $context, ResolveInfo $resolveInfo) use ($resolver): Deferred {
                $loader = BatchLoader::instance(
                    MissingRelationBatchLoader::class,
                    $resolveInfo->path,
                    [
                        'relationName'    => $this->directiveArgValue('relation', $this->nodeName()),
                        'decorateBuilder' => function ($query) use ($resolveInfo) {
                            $resolveInfo->argumentSet->enhanceBuilder(
                                $query,
                                $this->directiveArgValue('scopes', [])
                            );
                        },
                    ]
                );

                return new Deferred(function () use ($loader, $resolver, $parent, $args, $context, $resolveInfo) {
                    return $loader
                        ->load($parent->getKey(), ['parent' => $parent])
                        ->then(function () use ($resolver, $parent, $args, $context, $resolveInfo) {
                            return $resolver($parent, $args, $context, $resolveInfo);
                        });
                });
            })
        );
    }

    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Eager-load an Eloquent relation if it's missing.
"""
directive @withMissing(
  """
  Specify the relationship method name in the model class,
  if it is named different from the field in the schema.
  """
  relation: String

  """
  Apply scopes to the underlying query.
  """
  scopes: [String!]
) on FIELD_DEFINITION
SDL;
    }
}
