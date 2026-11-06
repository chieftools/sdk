<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use ChiefTools\SDK\Http\Middleware\TeamUrlContext;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();

    config(['chief.assets.provider' => 'vite']);

    Route::middleware(['web', TeamUrlContext::class])->group(static function () {
        Route::get('team/{team_hint}', static fn () => 'home');
        Route::get('team/{team_hint}/settings', static fn () => 'settings of ' . authenticated_user()->team->slug);
        Route::get('team/{team_hint}/billing', static fn () => 'billing')->middleware('can:manageBilling,team_hint');
    });

    $this->user  = createTeamContextUser();
    $this->amber = createTeamContextTeam('amber-grove', $this->user);
    $this->birch = createTeamContextTeam('birch-hollow', $this->user);
});

it('lets the user choose the team for the page, keeping the path and query', function () {
    $this->actingAs($this->user)
        ->get('/team/select/settings?tab=alerts')
        ->assertOk()
        ->assertSee('Select a team')
        ->assertSee('href="' . url('team/amber-grove/settings?tab=alerts') . '"', false)
        ->assertSee('href="' . url('team/birch-hollow/settings?tab=alerts') . '"', false);

    expect(session('chief_team_hint'))->toBeNull();
});

it('opens the page right away for the only team the user can open it for', function () {
    Gate::define('manageBilling', static fn ($user, $team): bool => $team->slug === 'birch-hollow');

    $this->actingAs($this->user)
        ->get('/team/select/billing')
        ->assertRedirect(url('team/birch-hollow/billing'));
});

it('shows the teams the user cannot open the page for without a link', function () {
    Gate::define('manageBilling', static fn (): bool => false);

    $this->actingAs($this->user)
        ->get('/team/select/billing')
        ->assertOk()
        ->assertSee("You don't have access to this page for any of your teams.")
        ->assertSee('birch-hollow')
        ->assertDontSee('href="' . url('team/birch-hollow/billing') . '"', false);
});

it('only offers the teams an admin is a member of', function () {
    $this->user->teams()->detach($this->birch);
    $this->user->forceFill(['is_admin' => true])->save();

    $this->actingAs($this->user)
        ->get('/team/select/settings')
        ->assertRedirect(url('team/amber-grove/settings'));
});

it('has no current team for the team selection', function () {
    $this->actingAs($this->user)->get('/team/select/settings');

    expect(fn () => $this->user->team)->toThrow(ChiefTools\SDK\Exceptions\NoCurrentTeam::class, 'The team selection has no current team');
});

it('keeps the query of a link to the page of a team without a path after the team', function () {
    $this->user->teams()->detach($this->birch);

    $this->actingAs($this->user)
        ->get('/team/select?ref=mail')
        ->assertRedirect(url('team/amber-grove?ref=mail'));
});
