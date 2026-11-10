<?php

use Illuminate\Http\JsonResponse;
use ChiefTools\SDK\Http\Controllers\API\GraphQL;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->user = createTeamContextUser();
    createTeamContextTeam('amber-grove', $this->user);

    $this->actingAs($this->user);
});

it('refuses session GraphQL requests that a cross-site page can send', function (string $method, string $contentType) {
    $this->call($method, route('api.web', ['query' => 'mutation { __typename }']), [], [], [], ['CONTENT_TYPE' => $contentType], 'query=mutation')
        ->assertBadRequest();
})->with([
    'GET request'     => ['GET', 'application/json'],
    'form post'       => ['POST', 'application/x-www-form-urlencoded'],
    'multipart post'  => ['POST', 'multipart/form-data'],
    'plain text post' => ['POST', 'text/plain'],
]);

it('lets session GraphQL requests sent as XHR reach the GraphQL controller', function () {
    $this->app->bind(GraphQL::class, static fn () => new class
    {
        public function __invoke(): JsonResponse
        {
            return response()->json(['reached' => true]);
        }
    });

    $this->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->postJson(route('api.web'), ['query' => 'mutation { __typename }'])
        ->assertOk()
        ->assertJsonPath('reached', true);
});
