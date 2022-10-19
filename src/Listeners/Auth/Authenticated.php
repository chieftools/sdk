<?php

namespace ChiefTools\SDK\Listeners\Auth;

use Sentry\State\Scope;
use Sentry\State\HubInterface;
use ChiefTools\SDK\Entities\User;
use Illuminate\Auth\Events\Authenticated as AuthenticatedEvent;

class Authenticated
{
    public function handle(AuthenticatedEvent $event): void
    {
        /** @var \ChiefTools\SDK\Entities\User $user */
        $user = $event->user;

        sync_user_timezone($user);

        $this->setupSentryContext($user);
    }

    private function setupSentryContext(User $user): void
    {
        if (!app()->bound(HubInterface::class)) {
            return;
        }

        app(HubInterface::class)->configureScope(static function (Scope $scope) use ($user) {
            $scope->setUser([
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'chief_id' => $user->chief_id,
            ]);
        });
    }
}
