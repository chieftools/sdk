<?php

use ChiefTools\SDK\UI\Shell\Commands;
use Illuminate\Support\Facades\Route;
use ChiefTools\SDK\Http\Controllers\Team\SwitchToGlobal;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config([
        'chief.global_team_home'                => 'start',
        'chief.shell.variant'                   => 'modern',
        'chief.shell.command_palette_providers' => ['serverchief.servers'],
    ]);

    Route::get('team/{team_hint}/start', static fn () => 'start')->allowsGlobalTeam()->name('start');
    Route::get('team/{team_hint}/overview', static fn () => 'overview')->allowsGlobalTeam()->name('overview');
    Route::get('team/{team_hint}/settings', static fn () => 'settings')->name('settings');
    Route::get('team/global/switch', SwitchToGlobal::class)->name('team.switch.global');
    Route::get('team/{team}/switch', static fn () => 'switch')->name('team.switch');
    Route::get('chief/ui/commands/search', static fn () => [])->name('chief.shell.commands.search');

    app('router')->getRoutes()->refreshNameLookups();

    $this->user  = createTeamContextUser();
    $this->amber = createTeamContextTeam('amber-grove', $this->user);
    $this->birch = createTeamContextTeam('birch-hollow', $this->user);

    $this->actingAs($this->user);
});

it('shows all teams in the shell of the global team view and searches every team from the palette', function () {
    $this->user->setGlobalTeamView();

    $html = view('chief::partial.menu', ['logoRedirect' => '/', 'menuItems' => []])->render();

    expect($html)
        ->toContain('data-team-slug="global"')
        ->toContain('All teams')
        ->toContain('Every team you are a member of')
        ->toContain('href="' . route('team.switch', [$this->amber]) . '"')
        ->toContain('href="' . route('team.switch', [$this->birch]) . '"')
        ->not->toContain('href="' . route('team.switch.global') . '"');
});

it('offers the global team view in the team menu of a team', function () {
    $this->user->setCurrentTeam($this->amber);

    $html = view('chief::partial.menu', ['logoRedirect' => '/', 'menuItems' => []])->render();

    expect($html)
        ->toContain('data-team-slug="amber-grove"')
        ->toContain('href="' . route('team.switch.global') . '"')
        ->toContain('href="' . route('team.switch', [$this->birch]) . '"')
        ->not->toContain('href="' . route('team.switch', [$this->amber]) . '"');
});

it('switches to the global team view on the page the user was on when it allows it', function () {
    session()->setPreviousUrl(url('team/amber-grove/overview'));

    expect(app(SwitchToGlobal::class)()->getTargetUrl())->toBe(url('team/global/overview'));
});

it('switches to the global team view on its home page from a page that needs a team', function () {
    session()->setPreviousUrl(url('team/amber-grove/settings'));

    expect(app(SwitchToGlobal::class)()->getTargetUrl())->toBe(url('team/global/start'));
});

it('shows a restricted team greyed out with a link to Account Chief instead of switching to it', function () {
    $this->user->teams()->updateExistingPivot($this->birch->id, ['access_restricted' => true]);
    $this->user->unsetRelation('teams');
    $this->user->setCurrentTeam($this->amber);

    $html = view('chief::partial.menu', ['logoRedirect' => '/', 'menuItems' => []])->render();

    expect($html)
        ->toContain('href="' . e(chief_base_url('team/birch-hollow')) . '"')
        ->toContain('data-team-access-restricted')
        ->toContain('Access restricted, see what to do')
        ->not->toContain('href="' . route('team.switch', [$this->birch]) . '"');
});

it('offers to restore access to a restricted team from the palette instead of switching to it', function () {
    $this->user->teams()->updateExistingPivot($this->birch->id, ['access_restricted' => true]);
    $this->user->unsetRelation('teams');

    $commands = Commands::teamSwitcher($this->user, $this->amber);

    expect($commands->firstWhere('label', 'Restore access to Birch Hollow team'))->toMatchArray([
        'href'   => chief_base_url('team/birch-hollow'),
        'target' => '_blank',
    ])->and($commands->firstWhere('label', 'Switch to Birch Hollow team'))->toBeNull();
});
