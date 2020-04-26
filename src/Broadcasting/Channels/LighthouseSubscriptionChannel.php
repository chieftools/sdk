<?php

namespace IronGate\Chief\Broadcasting\Channels;

use IronGate\Chief\Entities\User;
use Nuwave\Lighthouse\Subscriptions\Contracts\AuthorizesSubscriptions;

class LighthouseSubscriptionChannel
{
    private AuthorizesSubscriptions $subscriptionAuthorizer;

    public function __construct(AuthorizesSubscriptions $subscriptionAuthorizer)
    {
        $this->subscriptionAuthorizer = $subscriptionAuthorizer;
    }

    public function join(User $user): bool
    {
        return $this->subscriptionAuthorizer->authorize(request());
    }
}
