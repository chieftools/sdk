<?php

namespace IronGate\Chief\GraphQL\Resolvers;

use Illuminate\Support\Facades\Broadcast;
use IronGate\Chief\GraphQL\QueryResolver;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthenticateBroadcastChannel extends QueryResolver
{
    protected function execute(): ?array
    {
        if ($this->guest()) {
            return null;
        }

        if (!config('chief.graphql.subscriptions.enabled')) {
            return null;
        }

        $request = $this->request();

        $request->replace([
            'socket_id'    => $this->nestedInput('socketId'),
            'channel_name' => $this->nestedInput('channel'),
        ]);

        try {
            $result = Broadcast::auth($request);

            if ($result === null) {
                return null;
            }

            return [
                'token' => $result['auth'],
                'data'  => $result['channel_data'] ?? null,
            ];
        } catch (HttpException) {
            return null;
        }
    }
}
