<?php

use ChiefTools\SDK\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\RouteCollection;

/** Routes are registered while the application boots, so reload them after changing the configuration. */
function reloadWebRoutes(): void
{
    $router = app('router');

    $router->setRoutes(new RouteCollection);

    require ServiceProvider::basePath('routes/web.php');

    $router->getRoutes()->refreshNameLookups();
}

test('the legacy GraphQL documentation route redirects to the API documentation', function () {
    config(['chief.api_documentation.enabled' => true]);

    reloadWebRoutes();

    expect(Route::has('api.docs.graphql'))->toBeTrue()
        ->and(route('api.docs.graphql'))->toEndWith('/api/docs/graphql');
});

test('the legacy GraphQL documentation route is not registered when the API documentation is disabled', function () {
    config(['chief.api_documentation.enabled' => false]);

    reloadWebRoutes();

    expect(Route::has('api.docs'))->toBeFalse()
        ->and(Route::has('api.docs.graphql'))->toBeFalse()
        ->and(Route::has('api.tokens'))->toBeTrue();
});
