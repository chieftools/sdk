<?php

use Illuminate\Support\Facades\File;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['chief.assets.provider' => 'vite']);

    $this->withoutVite();

    $this->publicPath = sys_get_temp_dir() . '/chief-sdk-playground-' . bin2hex(random_bytes(4));

    File::copyDirectory(dirname(__DIR__, 3) . '/files/graphiql', $this->publicPath . '/js/graphiql');

    app()->usePublicPath($this->publicPath);
    app('view')->addLocation(dirname(__DIR__, 2) . '/Fixtures/views');

    $user = createTeamContextUser();
    createTeamContextTeam('amber-grove', $user);

    $this->actingAs($user);
});

afterEach(function () {
    File::deleteDirectory($this->publicPath);
});

it('serves the GraphiQL playground from the self-hosted bundle', function () {
    $manifest = json_decode(File::get($this->publicPath . '/js/graphiql/manifest.json'), true)['index.js'];

    $html = $this->get(route('api.playground'))->assertOk()->getContent();

    expect($html)
        ->toContain('<script type="module" src="' . asset('js/graphiql/' . $manifest['file']) . '"')
        ->toContain('<link rel="stylesheet" href="' . asset('js/graphiql/' . $manifest['css'][0]) . '"')
        ->toContain('data-endpoint="' . route('api.web') . '"')
        ->not->toContain('esm.sh')
        ->not->toContain('cdn.jsdelivr.net')
        ->not->toContain('unpkg.com');
});

it('enables GraphiQL subscriptions only when the app has GraphQL subscriptions enabled', function (bool $enabled) {
    config(['chief.graphql.subscriptions.enabled' => $enabled]);

    $html = $this->get(route('api.playground'))->assertOk()->getContent();

    expect(str_contains($html, 'data-subscriptions'))->toBe($enabled);
})->with([
    'enabled'  => [true],
    'disabled' => [false],
]);
