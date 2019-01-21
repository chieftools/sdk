<?php

use IronGate\Integration\Http\Controllers;

Route::group(['middleware' => config('chief.routes.middleware')], function () {
    Route::get('login', Controllers\Auth\Login::class)->name('auth.login');
    Route::get('logout', Controllers\Auth\Logout::class)->name('auth.logout');
    Route::get('login/callback', Controllers\Auth\Callback::class)->name('auth.callback');

    Route::post('webhooks/chief', Controllers\Webhook::class)->name('chief.webhook');
});

