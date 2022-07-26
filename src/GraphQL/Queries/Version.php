<?php

namespace ChiefTools\SDK\GraphQL\Queries;

use ChiefTools\SDK\GraphQL\QueryResolver;

class Version extends QueryResolver
{
    protected function execute(): string
    {
        return config('app.version');
    }
}
