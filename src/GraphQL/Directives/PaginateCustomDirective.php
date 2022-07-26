<?php

namespace ChiefTools\SDK\GraphQL\Directives;

use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Pagination\PaginateDirective;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaginateCustomDirective extends PaginateDirective
{
    public function resolveField(FieldValue $fieldValue): FieldValue
    {
        if ($this->directiveHasArgument('builder')) {
            return $fieldValue->setResolver(
                function ($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): LengthAwarePaginator {
                    return with_custom_pagination_resolver(function () use ($root, $args, $context, $resolveInfo) {
                        return call_user_func($this->getResolverFromArgument('builder'), $root, $args, $context, $resolveInfo);
                    }, static function ($pageName = 'page') use ($args) {
                        return $args[$pageName] ?? 1;
                    });
                }
            );
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
