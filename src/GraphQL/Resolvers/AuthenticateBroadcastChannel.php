<?php

namespace ChiefTools\SDK\GraphQL\Resolvers;

use Pusher\Pusher;
use ChiefTools\SDK\GraphQL\QueryResolver;
use Illuminate\Support\Facades\Broadcast;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Nuwave\Lighthouse\Subscriptions\Contracts\AuthorizesSubscriptions;

class AuthenticateBroadcastChannel extends QueryResolver
{
    protected function execute(): ?array
    {
        if (!config('chief.graphql.subscriptions.enabled')) {
            return null;
        }

        $request = $this->request();

        $request->replace([
            'socket_id'    => $this->nestedInput('socketId'),
            'channel_name' => $this->nestedInput('channel'),
        ]);

        // Lighthouse channels are authorized by the AuthorizesSubscriptions implementation instead to allow anonymous subscriptions
        if (str_starts_with($this->nestedInput('channel'), 'private-lighthouse-')) {
            $pusher = self::resolvePusher();

            if ($pusher !== null) {
                if (app(AuthorizesSubscriptions::class)->authorize($request)) {
                    $result = json_decode($pusher->authorizeChannel($this->nestedInput('channel'), $this->nestedInput('socketId')), true);

                    return [
                        'token' => $result['auth'],
                        'data'  => $result['channel_data'] ?? null,
                    ];
                }
            }

            return null;
        }

        if ($this->guest()) {
            return null;
        }

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

    public static function resolvePusher(): ?Pusher
    {
        // Try the default driver first
        $driver = Broadcast::driver();

        if ($driver instanceof PusherBroadcaster) {
            return $driver->getPusher();
        }

        // Try the pusher driver second
        $driver = Broadcast::driver('pusher');

        if ($driver instanceof PusherBroadcaster) {
            return $driver->getPusher();
        }

        return null;
    }
}
