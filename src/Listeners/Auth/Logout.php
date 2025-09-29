<?php

namespace ChiefTools\SDK\Listeners\Auth;

use ChiefTools\SDK\Entities\User;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Auth\Events\Logout as LogoutEvent;

class Logout
{
    public function handle(LogoutEvent $event): void
    {
        if (!$event->user instanceof User) {
            return;
        }

        Cookie::expire(config('chief.id') . '_auth');
    }
}
