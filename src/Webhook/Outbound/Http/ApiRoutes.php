<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http;

use Illuminate\Routing\Router;
use ChiefTools\SDK\Webhook\Outbound\Http\Controllers\Events;
use ChiefTools\SDK\Webhook\Outbound\Http\Controllers\Endpoints;
use ChiefTools\SDK\Webhook\Outbound\Http\Controllers\Deliveries;
use ChiefTools\SDK\Webhook\Outbound\Http\Controllers\EventTypes;

/**
 * The webhooks REST API, registered by apps inside their own authenticated and versioned API group with
 * `Route::chiefWebhooksApi('serverchief:webhooks')`. Reading needs the `{scope}:read` scope, changes `{scope}:write`.
 * Route names start with `webhooks.`, after the name prefix of the app's group.
 */
final class ApiRoutes
{
    public static function register(Router $router, string $scope, string $prefix = 'webhooks'): void
    {
        $read  = "scope:{$scope}:read";
        $write = "scope:{$scope}:write";

        $router->group(['as' => 'webhooks.', 'prefix' => $prefix], static function (Router $router) use ($read, $write): void {
            $router->get('event-types', EventTypes\Index::class)->name('event-types.index')->middleware($read);

            $router->get('endpoints', Endpoints\Index::class)->name('endpoints.index')->middleware($read);
            $router->post('endpoints', Endpoints\Create::class)->name('endpoints.create')->middleware($write);
            $router->get('endpoints/{endpoint}', Endpoints\Single::class)->name('endpoints.single')->middleware($read);
            $router->patch('endpoints/{endpoint}', Endpoints\Update::class)->name('endpoints.update')->middleware($write);
            $router->delete('endpoints/{endpoint}', Endpoints\Delete::class)->name('endpoints.delete')->middleware($write);
            $router->post('endpoints/{endpoint}/enable', Endpoints\Enable::class)->name('endpoints.enable')->middleware($write);
            $router->post('endpoints/{endpoint}/disable', Endpoints\Disable::class)->name('endpoints.disable')->middleware($write);
            $router->post('endpoints/{endpoint}/rotate-secret', Endpoints\RotateSecret::class)->name('endpoints.rotate-secret')->middleware($write);
            $router->post('endpoints/{endpoint}/test', Endpoints\SendTest::class)->name('endpoints.test')->middleware($write);
            $router->get('endpoints/{endpoint}/deliveries', Deliveries\Index::class)->name('deliveries.index')->middleware($read);

            $router->get('deliveries/{delivery}', Deliveries\Single::class)->name('deliveries.single')->middleware($read);
            $router->post('deliveries/{delivery}/resend', Deliveries\Resend::class)->name('deliveries.resend')->middleware($write);

            $router->get('events', Events\Index::class)->name('events.index')->middleware($read);
            $router->get('events/{event}', Events\Single::class)->name('events.single')->middleware($read);
            $router->post('events/{event}/resend', Events\Resend::class)->name('events.resend')->middleware($write);
        });
    }
}
