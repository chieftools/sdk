<?php

use IronGate\Integration\Http\Controllers;

Route::group(config('chief.routes.web'), function () {
    Route::get('login', Controllers\Auth\Login::class)->middleware('guest')->name('auth.login');
    Route::get('logout', Controllers\Auth\Logout::class)->middleware('auth')->name('auth.logout');
    Route::get('login/callback', Controllers\Auth\Callback::class)->middleware('guest')->name('auth.callback');

    Route::post('webhooks/chief', Controllers\Webhook::class)->name('chief.webhook');
});
