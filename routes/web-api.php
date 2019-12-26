<?php

use IronGate\Integration\Http\Controllers;

Route::group(config('chief.routes.web-api'), function () {
    Route::match(['get', 'post'], 'graphql/query', [Controllers\GraphQL::class, 'queryWeb'])->name('api.web');

    Route::view('api/playground', 'chief::api.playground')->name('api.playground');
});
