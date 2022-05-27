<?php

namespace IronGate\Chief\GraphQL\Mutations;

class Ping extends Mutation
{
    public function mutate(): ?array
    {
        return [
            'response' => 'pong',
        ];
    }
}
