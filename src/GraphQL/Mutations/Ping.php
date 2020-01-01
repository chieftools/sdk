<?php

namespace IronGate\Chief\GraphQL\Mutations;

class Ping
{
    public function __invoke(): string
    {
        return 'pong';
    }
}
