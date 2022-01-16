<?php

use IronGate\Chief\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::group(config('chief.routes.web-api'), function () {
    Route::match(['get', 'post'], 'graphql/web', Controllers\API\GraphQL::class)->middleware([
        Nuwave\Lighthouse\Support\Http\Middleware\AcceptJson::class,
        IronGate\Chief\GraphQL\Middleware\AuthenticateWeb::class,
    ])->name('api.web');

    Route::view('playground', 'chief::api.playground.' . config('chief.graphql.playground'))->name('api.playground');
});
