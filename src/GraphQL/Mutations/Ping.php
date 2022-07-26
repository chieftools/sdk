<?php

namespace ChiefTools\SDK\GraphQL\Mutations;

class Ping extends Mutation
{
    public function mutate(): ?array
    {
        return [
            'response' => 'pong',
        ];
    }
}
