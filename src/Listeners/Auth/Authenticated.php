<?php

namespace IronGate\Chief\Listeners\Auth;

use Illuminate\Auth\Events\Authenticated as AuthenticatedEvent;

class Authenticated
{
    public function handle(AuthenticatedEvent $event): void
    {
        sync_user_timezone($event->user);
    }
}
