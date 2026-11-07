<?php

use ChiefTools\SDK\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/** Routes are registered while the application boots, so reload them after changing the configuration. */
function reloadWebRoutes(): void
{
    $router = app('router');

    $router->setRoutes(new RouteCollection);

    require ServiceProvider::basePath('routes/web.php');

    $router->getRoutes()->refreshNameLookups();
}

test('the legacy GraphQL documentation route redirects to the API documentation', function () {
    config(['chief.api_documentation.enabled' => true]);

    reloadWebRoutes();

    expect(Route::has('api.docs.graphql'))->toBeTrue()
        ->and(route('api.docs.graphql'))->toEndWith('/api/docs/graphql');
});

test('the legacy GraphQL documentation route is not registered when the API documentation is disabled', function () {
    config(['chief.api_documentation.enabled' => false]);

    reloadWebRoutes();

    expect(Route::has('api.docs'))->toBeFalse()
        ->and(Route::has('api.docs.graphql'))->toBeFalse()
        ->and(Route::has('api.tokens'))->toBeTrue();
});

test('the account menus link to the API documentation of apps without API routes', function () {
    config(['chief.routes.api' => false, 'chief.api_documentation.enabled' => true]);

    reloadWebRoutes();

    actingAsDocumentationReader();

    expect(view('chief::partial.account.dropdown_items')->render())->toContain(route('api.docs'))
        ->and(view('chief::partial.menu_items')->render())->toContain(route('api.docs'));
});

test('the account menus leave out the API documentation when it is disabled', function () {
    config(['chief.routes.api' => false, 'chief.api_documentation.enabled' => false]);

    reloadWebRoutes();

    actingAsDocumentationReader();

    expect(view('chief::partial.account.dropdown_items')->render())->not->toContain('API documentation')
        ->and(view('chief::partial.menu_items')->render())->not->toContain('API documentation');
});

function actingAsDocumentationReader(): void
{
    $user = createTeamContextUser();
    createTeamContextTeam('amber-grove', $user);

    test()->actingAs($user);
}

test('the developer menus combine the API and MCP documentation in a single link', function () {
    config([
        'chief.auth.audiences.mcp'        => 'https://app.example.test/mcp',
        'chief.mcp.documentation_url'     => 'https://docs.example.test/app/ai',
        'chief.api_documentation.enabled' => true,
    ]);

    reloadWebRoutes();
    actingAsDocumentationReader();

    $dropdown = view('chief::partial.account.dropdown_items')->render();
    $menu     = view('chief::partial.menu_items')->render();

    expect($dropdown)->toContain(route('api.docs'))->toContain('API &amp; MCP documentation')->not->toContain('https://docs.example.test/app/ai')
        ->and($menu)->toContain(route('api.docs'))->toContain('API &amp; MCP documentation')->not->toContain('https://docs.example.test/app/ai')
        ->and(ChiefTools\SDK\UI\Shell\Commands::developer()->all())->toBe([[
            'label'       => 'API & MCP documentation',
            'href'        => route('api.docs'),
            'target'      => null,
            'icon'        => 'fad fa-rectangle-api',
            'description' => 'Developer',
        ]]);
});

test('the developer menus link to the MCP documentation of apps without API documentation', function () {
    config([
        'chief.auth.audiences.mcp'        => 'https://app.example.test/mcp',
        'chief.mcp.documentation_url'     => 'https://docs.example.test/app/ai',
        'chief.api_documentation.enabled' => false,
    ]);

    reloadWebRoutes();
    actingAsDocumentationReader();

    expect(view('chief::partial.account.dropdown_items')->render())->toContain('https://docs.example.test/app/ai')->toContain('MCP documentation')->toContain('target="_blank"')
        ->and(view('chief::partial.menu_items')->render())->toContain('https://docs.example.test/app/ai')
        ->and(ChiefTools\SDK\UI\Shell\Commands::developer()->sole())->toMatchArray([
            'label'  => 'MCP documentation',
            'href'   => 'https://docs.example.test/app/ai',
            'target' => '_blank',
        ]);
});

test('the developer menus leave out the MCP documentation of apps without an MCP server', function () {
    config([
        'chief.auth.audiences'            => [],
        'chief.mcp.endpoint'              => null,
        'chief.mcp.documentation_url'     => 'https://docs.example.test/app/ai',
        'chief.api_documentation.enabled' => false,
    ]);

    reloadWebRoutes();
    actingAsDocumentationReader();

    expect(view('chief::partial.account.dropdown_items')->render())->not->toContain('documentation')
        ->and(view('chief::partial.menu_items')->render())->not->toContain('documentation')
        ->and(ChiefTools\SDK\UI\Shell\Commands::developer())->toBeEmpty();
});

test('the API documentation shows the MCP server URL', function () {
    config([
        'chief.auth.audiences.mcp'    => 'https://app.example.test/mcp',
        'chief.mcp.documentation_url' => 'https://docs.example.test/app/ai',
    ]);

    expect(app(ChiefTools\SDK\Http\Controllers\API\Documentation::class)()->getData()['mcp'])->toBe([
        'endpoint'          => 'https://app.example.test/mcp',
        'documentation_url' => 'https://docs.example.test/app/ai',
    ]);

    config(['chief.mcp.endpoint' => 'https://mcp.example.test', 'chief.mcp.documentation_url' => null]);

    expect(app(ChiefTools\SDK\Http\Controllers\API\Documentation::class)()->getData()['mcp'])->toBe([
        'endpoint'          => 'https://mcp.example.test',
        'documentation_url' => null,
    ]);

    config(['chief.mcp.endpoint' => null, 'chief.auth.audiences' => []]);

    expect(app(ChiefTools\SDK\Http\Controllers\API\Documentation::class)()->getData()['mcp'])->toBeNull();
});

test('only http URLs are used for the MCP server', function () {
    config(['chief.mcp.endpoint' => 'javascript:alert(1)', 'chief.auth.audiences.mcp' => null, 'chief.mcp.documentation_url' => 'https://docs.example.test/app/ai']);

    expect(chief_mcp_endpoint())->toBeNull()
        ->and(chief_mcp_documentation_url())->toBeNull();

    config(['chief.mcp.endpoint' => 'https://app.example.test/mcp', 'chief.mcp.documentation_url' => 'ftp://docs.example.test/app/ai']);

    expect(chief_mcp_endpoint())->toBe('https://app.example.test/mcp')
        ->and(chief_mcp_documentation_url())->toBeNull();
});

test('the API documentation links to the webhooks guide and the webhooks of the team the user worked in last', function () {
    config(['chief.id' => 'lanternchief', 'chief.docs_url' => 'https://docs.example.test']);
    Route::get('team/{team_hint}/settings/webhooks', static fn (): null => null)->name('webhooks');
    app('router')->getRoutes()->refreshNameLookups();

    $webhooks = static fn (): ?array => app(ChiefTools\SDK\Http\Controllers\API\Documentation::class)()->getData()['webhooks'];

    // Without a team to go back to, the user chooses one
    expect($webhooks())->toBe([
        'documentation_url' => 'https://docs.example.test/developers/lanternchief/webhooks?ref=lanternchief',
        'manage_url'        => url('team/select/settings/webhooks'),
    ]);

    url()->defaults(['team_hint' => 'amber-grove']);

    expect($webhooks()['manage_url'])->toBe(url('team/amber-grove/settings/webhooks'));
});

test('the API documentation leaves out webhooks for apps that send none', function () {
    config(['chief.webhooks.enabled' => false]);

    expect(app(ChiefTools\SDK\Http\Controllers\API\Documentation::class)()->getData()['webhooks'])->toBeNull();
});
