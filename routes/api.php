<?php

use IronGate\Chief\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::get('.well-known/graphql.json', [Controllers\API\GraphQL::class, 'discovery'])->name('well-known.graphql');

Route::group(config('chief.routes.api'), function () {
    Route::match(['get', 'post'], 'graphql', Controllers\API\GraphQL::class)->middleware([
        Nuwave\Lighthouse\Support\Http\Middleware\AcceptJson::class,
        Nuwave\Lighthouse\Support\Http\Middleware\AttemptAuthentication::class . ':' . implode(',', config('chief.guards.api')),
    ])->name('api');

    Route::get('graphql/schema', [Controllers\API\GraphQL::class, 'schema'])->name('api.schema');

    if (config('chief.graphql.subscriptions.enabled')) {
        Route::post('graphql/subscriptions/webhook', Controllers\API\GraphQL\SubscriptionsWebhook::class)->name('api.subscriptions.webhook');
    }
});
