<?php

namespace IronGate\Chief\GraphQL\Queries;

use IronGate\Chief\GraphQL\QueryResolver;

class Ping extends QueryResolver
{
    protected function execute(): string
    {
        return 'pong';
    }
}
