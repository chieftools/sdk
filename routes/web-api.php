<?php

use IronGate\Integration\Http\Controllers;

Route::group([
    'middleware' => config('chief.routes.web-api.middleware'),
], function () {
    Route::match(['get', 'post'], 'graphql/query', [Controllers\GraphQL::class, 'queryWeb'])->name('api.web');

    Route::view('api/playground', 'chief::api.playground')->name('api.playground');
});
