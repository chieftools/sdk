<?php

namespace ChiefTools\SDK\Listeners\Auth;

use Illuminate\Auth\Events\Login as LoginEvent;

class Login
{
    public function handle(LoginEvent $event): void
    {
        /** @var \ChiefTools\SDK\Entities\User $user */
        $user = $event->user;

        $user->last_login = now();

        $user->save();
    }
}
