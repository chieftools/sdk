<?php

namespace ChiefTools\SDK\GraphQL\Directives;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Pagination\Paginator;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Pagination\PaginateDirective;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class PaginateCustomDirective extends PaginateDirective
{
    public function resolveField(FieldValue $fieldValue): callable
    {
        if ($this->directiveHasArgument('builder')) {
            return function (mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Paginator {
                return with_custom_pagination_resolver(function () use ($root, $args, $context, $resolveInfo) {
                    return call_user_func($this->getResolverFromArgument('builder'), $root, $args, $context, $resolveInfo);
                }, static function ($pageName = 'page') use ($args) {
                    return $args[$pageName] ?? 1;
                });
            };
        }

        return parent::resolveField($fieldValue);
    }

    public static function definition(): string
    {
        return str_replace([
            '@paginate(',
            'PaginateType',
        ], [
            '@paginateCustom(',
            'PaginateCustomType',
        ], parent::definition());
    }
}
