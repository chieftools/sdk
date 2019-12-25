<?php

use IronGate\Integration\Http\Controllers;

Route::group([
    'middleware' => config('chief.routes.web.middleware'),
], function () {
    Route::get('login', Controllers\Auth\Login::class)->middleware('guest')->name('auth.login');
    Route::get('logout', Controllers\Auth\Logout::class)->middleware('auth')->name('auth.logout');
    Route::get('login/callback', Controllers\Auth\Callback::class)->middleware('guest')->name('auth.callback');

    Route::post('webhooks/chief', Controllers\Webhook::class)->name('chief.webhook');
});

if (config('chief.routes.web.api')) {
    Route::group([
        'middleware' => config('chief.routes.web.api.middleware'),
    ], function () {
        Route::match(['get', 'post'], 'graphql/query', [Controllers\GraphQL::class, 'queryWeb'])->name('api.web');

        Route::view('api/playground', 'chief::api.playground')->name('api.playground');
    });
}
