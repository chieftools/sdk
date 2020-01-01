<?php

use IronGate\Chief\Http\Controllers;

Route::group(config('chief.routes.web-api'), function () {
    Route::match(['get', 'post'], 'graphql/web', [Controllers\API\GraphQL::class, 'queryWeb'])->name('api.web');

    Route::view('playground', 'chief::api.playground')->name('api.playground');
});
