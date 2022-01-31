<?php

namespace IronGate\Chief\Listeners\Auth;

use Sentry\State\Scope;
use IronGate\Chief\Entities\User;
use Illuminate\Auth\Events\Authenticated as AuthenticatedEvent;

class Authenticated
{
    public function handle(AuthenticatedEvent $event): void
    {
        /** @var \IronGate\Chief\Entities\User $user */
        $user = $event->user;

        sync_user_timezone($user);

        $this->setupSentryContext($user);
    }

    private function setupSentryContext(User $user): void
    {
        if (!app()->bound('sentry')) {
            return;
        }

        /** @var \Sentry\State\Hub $sentry */
        $sentry = app('sentry');

        $sentry->configureScope(static function (Scope $scope) use ($user) {
            $scope->setUser([
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'chief_id' => $user->chief_id,
            ]);
        });
    }
}
