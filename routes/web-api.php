<?php

use ChiefTools\SDK\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::group(config('chief.routes.web-api'), function () {
    Route::get('chief/ui/theme/{theme}', Controllers\Shell\Theme::class)
        ->whereIn('theme', ['light', 'dark', 'system'])
        ->middleware('throttle:30,1')
        ->name('chief.shell.theme');

    Route::get('chief/ui/commands/search', Controllers\Shell\CommandsSearch::class)
        ->middleware('throttle:60,1')
        ->name('chief.shell.commands.search');

    Route::match(['get', 'post'], 'graphql/web', Controllers\API\GraphQL::class)->middleware([
        Nuwave\Lighthouse\Http\Middleware\AcceptJson::class,
        ChiefTools\SDK\GraphQL\Middleware\AuthenticateWeb::class,
    ])->name('api.web');

    Route::view('playground', 'chief::api.playground.' . config('chief.graphql.playground'))->name('api.playground');
});
