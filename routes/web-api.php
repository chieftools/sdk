<?php

use ChiefTools\SDK\Http\Controllers;
use Illuminate\Support\Facades\Route;

$authenticationMiddleware = collect(config('chief.routes.web-api.middleware', []))
    ->filter(static function (string $middleware): bool {
        return $middleware === 'auth'
            || str_starts_with($middleware, 'auth:')
            || str_starts_with($middleware, 'auth.')
            || $middleware === Illuminate\Auth\Middleware\Authenticate::class;
    })
    ->values()
    ->all();

Route::group(config('chief.routes.web-api'), function () use ($authenticationMiddleware) {
    Route::get('chief/ui/theme/{theme}', Controllers\Shell\Theme::class)
        ->whereIn('theme', ['light', 'dark', 'system'])
        ->middleware('throttle:30,1')
        ->withoutMiddleware($authenticationMiddleware)
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
