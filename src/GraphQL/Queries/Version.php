<?php

namespace IronGate\Chief\GraphQL\Queries;

class Version
{
    public function __invoke(): string
    {
        return config('app.version');
    }
}
