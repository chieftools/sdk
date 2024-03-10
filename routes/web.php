<?php

use ChiefTools\SDK\Http\Controllers;
use Illuminate\Support\Facades\Route;
use ChiefTools\SDK\Http\Middleware\AuthenticateChief;

Route::redirect('.well-known/security.txt', chief_site_url('.well-known/security.txt'))->name('well-known.security');
Route::redirect('.well-known/change-password', chief_base_url('account/password'))->name('well-known.change-password');

Route::group(config('chief.routes.web'), function () {
    if (config('chief.auth')) {
        Route::redirect('auth/login', '/login', 301)->name('login');
        Route::redirect('auth/register', '/register', 301)->name('register');

        Route::group([
            'as' => 'auth.',
        ], function () {
            Route::get('register', Controllers\Auth\Register::class)->middleware('guest')->name('register');

            Route::get('login', Controllers\Auth\Login::class)->middleware('guest')->name('login');
            Route::get('login/callback', Controllers\Auth\Callback::class)->middleware('guest')->name('callback');

            Route::post('logout', Controllers\Auth\Logout::class)->middleware('auth')->name('logout');
        });
    }

    Route::group([
        'as' => 'chief.',
    ], function () {
        Route::get('about', Controllers\Pages\About::class)->name('about');
        Route::get('abuse', Controllers\Pages\Abuse::class)->name('abuse');
        Route::get('terms', Controllers\Pages\Terms::class)->name('terms');
        Route::get('contact', Controllers\Pages\Contact::class)->name('contact');
        Route::get('privacy', Controllers\Pages\Privacy::class)->name('privacy');

        Route::post('webhooks/chief', Controllers\Webhook::class)->middleware(AuthenticateChief::class)->name('webhook');
    });

    if (config('chief.auth') && config('chief.auth.account')) {
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
            'as'         => 'team.',
            'prefix'     => 'team',
            'middleware' => 'auth',
        ], function () {
            Route::get('{team}/switch', Controllers\Team\SwitchActive::class)->name('switch');
            Route::get('{team}/chief', Controllers\Team\Manage::class)->name('chief.manage');
            Route::get('{team}/chief/manage', Controllers\Team\ManageSingle::class)->name('chief.manage.single');
            Route::get('{team}/chief/manage/plan', Controllers\Team\ManagePlan::class)->name('chief.manage.plan');
        });

        Route::group([
            'as'         => 'api.',
            'prefix'     => 'api',
            'middleware' => 'auth',
        ], function () {
            Route::view('docs/graphql', 'chief::api.docs.graphql')->name('docs.graphql');

            Route::get('tokens', Controllers\API\Tokens::class)->name('tokens');
            Route::get('token/create', [Controllers\API\Tokens::class, 'create'])->name('tokens.create');
            Route::post('token/{id}/delete', [Controllers\API\Tokens::class, 'delete'])->name('tokens.delete');
        });
    }
});
