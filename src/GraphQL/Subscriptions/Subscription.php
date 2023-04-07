<?php

namespace ChiefTools\SDK\GraphQL\Subscriptions;

use Illuminate\Http\Request;
use Nuwave\Lighthouse\Subscriptions\Subscriber;
use Nuwave\Lighthouse\Schema\Types\GraphQLSubscription;

abstract class Subscription extends GraphQLSubscription
{
    public function can(Subscriber $subscriber): bool
    {
        return $subscriber->context->user() !== null;
    }

    public function filter(Subscriber $subscriber, $root): bool
    {
        return true;
    }

    public function authorize(Subscriber $subscriber, Request $request): bool
    {
        return true;
    }

    public function encodeTopic(Subscriber $subscriber, string $fieldName): string
    {
        return strtolower(snake_case($fieldName));
    }

    public function decodeTopic(string $fieldName, $root): string
    {
        return strtolower(snake_case($fieldName));
    }

    public static function dispatch($root, ?bool $shouldQueue = null): void
    {
        dispatch_subscription(static::class, $root, $shouldQueue);
    }
}
