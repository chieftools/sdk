<?php

namespace IronGate\Integration\GraphQL\Mutations;

class Ping
{
    public function __invoke(): string
    {
        return 'pong';
    }
}
