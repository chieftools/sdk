<?php

use ChiefTools\SDK\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::group(config('chief.routes.api'), function () {
    Route::get('.well-known/graphql.json', [Controllers\API\GraphQL::class, 'discovery'])->name('well-known.graphql');

    Route::get('graphql/schema', [Controllers\API\GraphQL::class, 'schema'])->name('api.schema');

    Route::match(['get', 'post'], 'graphql', Controllers\API\GraphQL::class)->middleware([
        Nuwave\Lighthouse\Http\Middleware\AcceptJson::class,
        Nuwave\Lighthouse\Http\Middleware\AttemptAuthentication::class . ':' . implode(',', config('chief.guards.api')),
    ])->name('api');

    if (config('chief.graphql.federation.enabled')) {
        Route::match(['get', 'post'], 'graphql/federated', [Controllers\API\GraphQL::class, 'federated'])->middleware([
            Nuwave\Lighthouse\Http\Middleware\AcceptJson::class,
            Nuwave\Lighthouse\Http\Middleware\AttemptAuthentication::class . ':' . implode(',', config('chief.guards.api')),
        ]);
    }

    if (config('chief.graphql.subscriptions.enabled')) {
        Route::post('graphql/subscriptions/webhook', Controllers\API\GraphQL\SubscriptionsWebhook::class)->name('api.subscriptions.webhook');
    }
});
