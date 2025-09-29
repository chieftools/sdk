<?php

namespace ChiefTools\SDK\Listeners\Auth;

use ChiefTools\SDK\Entities\User;
use Illuminate\Support\Facades\Cookie;
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

        Cookie::queue(
            Cookie::make(
                name: config('chief.id') . '_auth',
                value: '1',
                secure: true,
                minutes: 10080, // 7 days
            ),
        );
    }
}
