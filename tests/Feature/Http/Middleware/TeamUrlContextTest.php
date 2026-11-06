<?php

use ChiefTools\SDK\Chief;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use ChiefTools\SDK\Exceptions\NoCurrentTeam;
use Symfony\Component\HttpFoundation\Response;
use ChiefTools\SDK\Http\Middleware\TeamUrlContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Route::get('team/{team_hint}/dashboard', static fn () => 'dashboard');
    Route::get('tlds', static fn () => 'tlds');
    Route::get('api/chief/ui/commands/search', static fn () => 'search');
    Route::get('team/{team_hint}/overview', static fn () => 'overview')->allowsGlobalTeam();
    Route::get('api/everywhere', static fn () => 'everywhere')->allowsGlobalTeam();
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

it('uses the team from the team header of a request without a team in the URL without remembering it', function () {
    session()->put('chief_team_hint', 'amber-grove');

    $search = teamUrlContextRequest('GET', '/api/chief/ui/commands/search');

    $search->headers->set('X-Chief-Team', 'birch-hollow');

    handleTeamUrlContext($search, $search);

    expect($this->user->team->slug)->toBe('birch-hollow')
        ->and(session('chief_team_hint'))->toBe('amber-grove');
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

it('binds the global team hint only when the app enables the global team view, and the team selection hint always', function () {
    $binding = app('router')->getBindingCallback('team_hint');

    expect(static fn () => $binding('global'))->toThrow(ModelNotFoundException::class)
        ->and($binding('select'))->toBe(Chief::SELECT_TEAM_HINT);

    config(['chief.global_team_home' => 'overview']);

    expect($binding('global'))->toBe(Chief::GLOBAL_TEAM_HINT)
        ->and($binding('birch-hollow')->slug)->toBe('birch-hollow');
});

it('opens the global team view with every team of the user and no current team, without remembering it', function () {
    config(['chief.global_team_home' => 'overview']);

    session()->put('chief_team_hint', 'amber-grove');

    $page = teamUrlContextRequest('GET', '/team/global/overview');

    expect(handleTeamUrlContext($page, $page)->getContent())->toBe('next')
        ->and($this->user->inGlobalTeamView())->toBeTrue()
        ->and($this->user->teamsInContext()->pluck('slug')->sort()->values()->all())->toBe(['amber-grove', 'birch-hollow'])
        ->and(url()->getDefaultParameters()['team_hint'])->toBe('global')
        ->and(session('chief_team_hint'))->toBe('amber-grove')
        ->and(fn () => $this->user->team)->toThrow(NoCurrentTeam::class)
        ->and(fn () => app(ChiefTools\SDK\Entities\Team::class))->toThrow(NoCurrentTeam::class);
});

it('asks to choose the team for a page that needs one when it is linked from the global team view', function () {
    config(['chief.global_team_home' => 'overview']);

    $page = teamUrlContextRequest('GET', '/team/global/dashboard?tab=open');

    expect(handleTeamUrlContext($page, $page)->headers->get('Location'))->toEndWith('/team/select/dashboard?tab=open');
});

it('opens a page that needs a team for the team the app names for a link from the global team view', function () {
    config(['chief.global_team_home' => 'overview']);

    createTeamContextTeam('cedar-ridge');

    $page = teamUrlContextRequest('GET', '/team/global/dashboard?tab=open');

    Chief::registerGlobalTeamLinkResolver(static fn () => ChiefTools\SDK\Entities\Team::query()->where('slug', 'birch-hollow')->first());

    expect(handleTeamUrlContext($page, $page)->headers->get('Location'))->toEndWith('/team/birch-hollow/dashboard?tab=open');

    // Never a team the user is not a member of
    Chief::registerGlobalTeamLinkResolver(static fn () => ChiefTools\SDK\Entities\Team::query()->where('slug', 'cedar-ridge')->first());

    expect(handleTeamUrlContext($page, $page)->headers->get('Location'))->toEndWith('/team/select/dashboard?tab=open');
})->after(fn () => Chief::registerGlobalTeamLinkResolver(null));

it('keeps a Livewire component of the global team view in the global team view', function () {
    config(['chief.global_team_home' => 'overview']);

    session()->put('chief_team_hint', 'amber-grove');

    $update = teamUrlContextRequest('POST', '/livewire/update');

    app()->instance('request', $update);

    expect($this->user->team->slug)->toBe('amber-grove');

    handleTeamUrlContext($update, teamUrlContextRequest('GET', '/team/global/overview'));

    expect($this->user->inGlobalTeamView())->toBeTrue()
        ->and(fn () => $this->user->team)->toThrow(NoCurrentTeam::class);
});

it('opens the global team view from the team header only on routes that allow it', function () {
    config(['chief.global_team_home' => 'overview']);

    $everywhere = teamUrlContextRequest('GET', '/api/everywhere');
    $everywhere->headers->set('X-Chief-Team', 'global');

    handleTeamUrlContext($everywhere, $everywhere);

    expect($this->user->inGlobalTeamView())->toBeTrue();

    $this->user->clearCurrentTeam();

    $search = teamUrlContextRequest('GET', '/api/chief/ui/commands/search');
    $search->headers->set('X-Chief-Team', 'global');

    expect(static fn () => handleTeamUrlContext($search, $search))->toThrow(HttpException::class, 'The global team view is not available for this request.');
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
