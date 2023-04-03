<?php

use ChiefTools\SDK\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::group(config('chief.routes.web-api'), function () {
    Route::match(['get', 'post'], 'graphql/web', Controllers\API\GraphQL::class)->middleware([
        Nuwave\Lighthouse\Http\Middleware\AcceptJson::class,
        ChiefTools\SDK\GraphQL\Middleware\AuthenticateWeb::class,
    ])->name('api.web');

    Route::view('playground', 'chief::api.playground.' . config('chief.graphql.playground'))->name('api.playground');
});
