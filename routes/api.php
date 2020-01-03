<?php

use IronGate\Chief\Http\Controllers;

Route::group(config('chief.routes.api'), function () {
    Route::match(['get', 'post'], 'graphql', [Controllers\API\GraphQL::class, 'query'])->name('api');

    Route::get('graphql/schema', [Controllers\API\GraphQL::class, 'schema'])->name('api.schema');
});
