<?php

namespace ChiefTools\SDK\GraphQL\Queries;

use ChiefTools\SDK\GraphQL\QueryResolver;

class Ping extends QueryResolver
{
    protected function execute(): string
    {
        return 'pong';
    }
}
