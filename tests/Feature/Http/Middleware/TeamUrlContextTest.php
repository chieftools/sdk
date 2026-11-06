<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;
use ChiefTools\SDK\Http\Middleware\TeamUrlContext;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Route::get('team/{team_hint}/dashboard', static fn () => 'dashboard');
    Route::get('tlds', static fn () => 'tlds');
    Route::post('livewire/update', static fn () => 'update')->name('default-livewire.update');

    $this->user = createTeamContextUser();

    createTeamContextTeam('amber-grove', $this->user);
    createTeamContextTeam('birch-hollow', $this->user);

    $this->actingAs($this->user);
});

it('remembers the team of a page in the session', function () {
    session()->put('chief_team_hint', 'amber-grove');

    $page = teamUrlContextRequest('GET', '/team/birch-hollow/dashboard');

    handleTeamUrlContext($page, $page);

    expect($this->user->currentTeam()->slug)->toBe('birch-hollow')
        ->and(session('chief_team_hint'))->toBe('birch-hollow');
});

it('leaves the team to the page of the component on the Livewire update endpoint', function () {
    session()->put('chief_team_hint', 'amber-grove');

    $update = teamUrlContextRequest('POST', '/livewire/update');

    $response = handleTeamUrlContext($update, $update);

    expect($response->getContent())->toBe('next')
        ->and($this->user->getMemoizedCurrentTeam())->toBeNull();
});

it('uses the team of the page for a Livewire component update instead of the team in the session', function () {
    session()->put('chief_team_hint', 'amber-grove');

    $update = teamUrlContextRequest('POST', '/livewire/update');

    app()->instance('request', $update);

    // Resolved from the session before Livewire runs the middleware again for the page of the component
    expect($this->user->team->slug)->toBe('amber-grove');

    handleTeamUrlContext($update, teamUrlContextRequest('GET', '/team/birch-hollow/dashboard'));

    expect($this->user->team->slug)->toBe('birch-hollow')
        ->and(url()->getDefaultParameters()['team_hint'])->toBe('birch-hollow')
        ->and(session('chief_team_hint'))->toBe('amber-grove');
});

it('uses the team in the session for a Livewire component update on a page without a team in the URL', function () {
    session()->put('chief_team_hint', 'birch-hollow');

    $update = teamUrlContextRequest('POST', '/livewire/update');

    handleTeamUrlContext($update, teamUrlContextRequest('GET', '/tlds'));

    expect($this->user->currentTeam()->slug)->toBe('birch-hollow')
        ->and(session('chief_team_hint'))->toBe('birch-hollow');
});

function teamUrlContextRequest(string $method, string $uri): Request
{
    $request = Request::create($uri, $method);
    $route   = app('router')->getRoutes()->match($request);

    $request->setRouteResolver(static fn () => $route);
    $request->setUserResolver(app('auth')->userResolver());

    return $request;
}

/** The current request is the request Laravel handles, Livewire passes a request for the page of the component to persistent middleware. */
function handleTeamUrlContext(Request $current, Request $handled): Response
{
    app()->instance('request', $current);

    return app(TeamUrlContext::class)->handle($handled, static fn (): Response => new Response('next'));
}
