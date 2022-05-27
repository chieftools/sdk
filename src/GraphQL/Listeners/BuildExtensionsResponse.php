<?php

namespace IronGate\Chief\GraphQL\Listeners;

use Pusher\Pusher;
use Nuwave\Lighthouse\Execution\ExtensionsResponse;
use Nuwave\Lighthouse\Subscriptions\SubscriptionRegistry;
use Nuwave\Lighthouse\Events\BuildExtensionsResponse as Event;

class BuildExtensionsResponse
{
    public function handle(Event $event): ?ExtensionsResponse
    {
        if (!config('chief.graphql.subscriptions.enabled') || config('lighthouse.subscriptions.broadcaster') !== 'pusher') {
            return null;
        }

        $socketId = request()?->header('chief-socket-id');

        if (empty($socketId)) {
            return null;
        }

        $registry = app(SubscriptionRegistry::class);

        $subscribers = __access_class_property($registry, 'subscribers');

        $channel = count($subscribers) > 0
            ? reset($subscribers)
            : null;

        if ($channel === null) {
            return null;
        }

        return new ExtensionsResponse('chief_socket', [
            'auth' => app(Pusher::class)->socketAuth($channel, $socketId),
        ]);
    }
}
