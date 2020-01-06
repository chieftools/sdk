<?php

use IronGate\Chief\Http\Controllers;
use IronGate\Chief\Http\Middleware\AuthenticateChief;

Route::group(config('chief.routes.web'), function () {
    Route::redirect('auth/login', 'login')->name('login');
    Route::redirect('auth/register', 'register')->name('register');

    Route::get('register', Controllers\Auth\Register::class)->name('auth.register');

    Route::get('login', Controllers\Auth\Login::class)->middleware('guest')->name('auth.login');
    Route::get('login/callback', Controllers\Auth\Callback::class)->middleware('guest')->name('auth.callback');

    Route::get('logout', Controllers\Auth\Logout::class)->middleware('auth')->name('auth.logout');

    Route::get('terms', Controllers\Pages\Terms::class)->name('chief.terms');
    Route::get('contact', Controllers\Pages\Contact::class)->name('chief.contact');
    Route::get('privacy', Controllers\Pages\Privacy::class)->name('chief.privacy');

    Route::post('webhooks/chief', Controllers\Webhook::class)->middleware(AuthenticateChief::class)->name('chief.webhook');

    Route::group([
        'as'         => 'account.',
        'prefix'     => 'account',
        'middleware' => 'auth',
    ], function () {

        Route::view('profile', 'chief::account.profile')->name('profile');
        Route::get('preferences', Controllers\Account\Preferences::class)->name('preferences');
        Route::post('preference/toggle', [Controllers\Account\Preferences::class, 'toggle'])->name('preferences.toggle');

    });

    Route::group([
        'as'         => 'api.',
        'prefix'     => 'api',
        'middleware' => 'auth',
    ], function () {

        Route::view('docs/graphql', 'chief::api.docs.graphql')->name('docs.graphql');

        Route::get('tokens', Controllers\API\Tokens::class)->name('tokens');
        Route::get('token/create', [Controllers\API\Tokens::class, 'create'])->name('tokens.create');
        Route::post('token/create', [Controllers\API\Tokens::class, 'store']);
        Route::post('token/{id}/delete', [Controllers\API\Tokens::class, 'delete'])->name('tokens.delete');

    });
});
