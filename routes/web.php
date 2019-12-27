<?php

use IronGate\Integration\Http\Controllers;
use IronGate\Integration\Http\Middleware\AuthenticateChief;

Route::group(config('chief.routes.web'), function () {
    Route::get('register', Controllers\Auth\Register::class)->name('auth.register');

    Route::get('login', Controllers\Auth\Login::class)->middleware('guest')->name('auth.login');
    Route::get('login/callback', Controllers\Auth\Callback::class)->middleware('guest')->name('auth.callback');

    Route::get('logout', Controllers\Auth\Logout::class)->middleware('auth')->name('auth.logout');

    Route::get('terms', Controllers\Pages\Terms::class)->name('chief.terms');
    Route::get('privacy', Controllers\Pages\Privacy::class)->name('chief.privacy');

    Route::post('webhooks/chief', Controllers\Webhook::class)->middleware(AuthenticateChief::class)->name('chief.webhook');
});
