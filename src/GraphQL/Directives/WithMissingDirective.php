<?php

namespace IronGate\Chief\GraphQL\Directives;

use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;
use Nuwave\Lighthouse\Support\Contracts\DefinedDirective;
use IronGate\Chief\GraphQL\Support\MissingRelationBatchLoader;
use Nuwave\Lighthouse\Schema\Directives\WithRelationDirective;

class WithMissingDirective extends WithRelationDirective implements FieldMiddleware, DefinedDirective
{
    public function batchLoaderClass(): string
    {
        return MissingRelationBatchLoader::class;
    }

    public function relationName(): string
    {
        return $this->directiveArgValue('relation', $this->nodeName());
    }

    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Eager-load a missing Eloquent relation.
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
