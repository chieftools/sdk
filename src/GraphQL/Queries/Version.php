<?php

namespace IronGate\Chief\GraphQL\Queries;

use IronGate\Chief\GraphQL\QueryResolver;

class Version extends QueryResolver
{
    protected function execute(): string
    {
        return config('app.version');
    }
}
