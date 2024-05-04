<?php

namespace ChiefTools\SDK\Listeners\Auth;

use ChiefTools\SDK\Entities\User;
use Illuminate\Auth\Events\Login as LoginEvent;

class Login
{
    public function handle(LoginEvent $event): void
    {
        if (!$event->user instanceof User) {
            return;
        }

        $user = $event->user;

        $user->last_login = now();

        $user->save();
    }
}
