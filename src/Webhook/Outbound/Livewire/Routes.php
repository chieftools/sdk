<?php

namespace ChiefTools\SDK\Webhook\Outbound\Livewire;

use Illuminate\Routing\Router;

/**
 * The webhooks pages, registered by the app with `Route::chiefWebhooks('settings/webhooks')` in its team route group.
 */
final class Routes
{
    public static function register(Router $router, string $prefix = 'webhooks'): void
    {
        $prefix = trim($prefix, '/');

        $router->livewire($prefix, Endpoints::class)->name('webhooks');
        $router->livewire("{$prefix}/events", Events::class)->name('webhooks.events');
        $router->livewire("{$prefix}/{endpointId}", EndpointDetail::class)->name('webhooks.endpoint');
    }
}
