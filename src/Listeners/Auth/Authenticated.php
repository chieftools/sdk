<?php

namespace ChiefTools\SDK\Listeners\Auth;

use Sentry\State\Scope;
use Sentry\State\HubInterface;
use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\Entities\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Auth\Events\Authenticated as AuthenticatedEvent;

class Authenticated
{
    public function handle(AuthenticatedEvent $event): void
    {
        /** @var \ChiefTools\SDK\Entities\User|\ChiefTools\SDK\Entities\Team $authenticatable */
        $authenticatable = $event->user;

        if ($authenticatable instanceof User && $authenticatable->exists && !$authenticatable->hasApplicationAccess()) {
            $guard = Auth::guard($event->guard);

            if ($guard instanceof StatefulGuard) {
                $guard->logout();

                if (request()->hasSession()) {
                    request()->session()->invalidate();
                    request()->session()->regenerateToken();
                }
            }

            throw new AuthenticationException(
                'The user does not have access to applications.',
                [$event->guard],
            );
        }

        sync_user_timezone($authenticatable);

        $this->setupSentryContext($authenticatable);
    }

    private function setupSentryContext(User|Team $authenticatable): void
    {
        if (!app()->bound(HubInterface::class)) {
            return;
        }

        app(HubInterface::class)->configureScope(static function (Scope $scope) use ($authenticatable) {
            if ($authenticatable instanceof User) {
                $scope->setUser([
                    'id'       => $authenticatable->id,
                    'name'     => $authenticatable->name,
                    'email'    => $authenticatable->email,
                    'chief_id' => $authenticatable->hasAttribute('chief_id') ? $authenticatable->getAttribute('chief_id') : null,
                ]);
            }

            if ($authenticatable instanceof Team) {
                $scope->setUser([
                    'id'   => $authenticatable->id,
                    'name' => "Team: {$authenticatable->name}",
                    'slug' => $authenticatable->slug,
                ]);
            }
        });
    }
}
