<?php

namespace ChiefTools\SDK\Broadcasting\Channels;

use ChiefTools\SDK\Entities\User;
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
