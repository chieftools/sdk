<?php

use IronGate\Integration\Http\Controllers;

Route::group([
    'middleware' => config('chief.routes.api.middleware'),
], function () {
    Route::get('graphql/schema', [Controllers\GraphQL::class, 'schema'])->name('api.schema');
    Route::match(['get', 'post'], 'graphql', [Controllers\GraphQL::class, 'query'])->name('api');
});
