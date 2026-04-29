<?php

use Tests\TestCase;
use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\Entities\User;
use Illuminate\Support\Facades\Route;

uses(TestCase::class);

beforeEach(function () {
    config(['app.key' => 'base64:oE72uRMtvwHlVTVBthR+K3FBDmSqNXTevcEU2LtLqrw=']);
    request()->cookies->remove('chief_shell_theme');
});

test('the legacy shell remains the default menu renderer', function () {
    $html = view('chief::partial.menu', [
        'logoRedirect' => '/',
        'menuItems'    => [
            [
                'href'   => '/dashboard',
                'icon'   => 'fad fa-dashboard',
                'text'   => 'Dashboard',
                'active' => true,
            ],
        ],
    ])->render();

    expect($html)
        ->toContain('main-menu-items')
        ->toContain('Dashboard')
        ->not->toContain('data-chief-shell');
});

test('the modern shell renders configured menu and app switcher data', function () {
    if (!Route::has('chief.shell.commands.search')) {
        Route::get('chief/ui/commands/search', static fn (): array => [])->name('chief.shell.commands.search');
    }

    config([
        'chief.id'            => 'domainchief',
        'chief.shell.variant' => 'modern',
        'chief.brand.icon'    => 'fa-globe',
        'chief.brand.color'   => '#3498db',
    ]);

    auth()->setUser(new User([
        'name'  => 'Alex',
        'email' => 'alex@example.com',
    ]));
    request()->attributes->set('team_hint', (new Team())->forceFill([
        'name' => 'Current team',
        'slug' => 'current',
    ]));

    $html = view('chief::partial.menu', [
        'logoRedirect' => '/',
        'shellTool'    => [
            'name'         => 'Domain Chief',
            'logoColorUrl' => '/icons/domainchief.svg',
        ],
        'menuItems'    => [
            [
                'href'   => '/dashboard',
                'icon'   => 'fad fa-dashboard',
                'text'   => 'Dashboard',
                'active' => true,
            ],
        ],
        'shellApps'    => [
            [
                'id'           => 'accountchief',
                'name'         => 'Account Chief',
                'icon'         => 'fa-toolbox',
                'color'        => '#34495e',
                'href'         => 'https://account.chief.app',
                'url_template' => 'https://account.chief.app/team/{team}',
                'description'  => 'Account and billing',
            ],
        ],
        'shellCommands' => [
            [
                'label'       => 'Dashboard',
                'href'        => '/dashboard',
                'icon'        => 'fad fa-dashboard',
                'category'    => 'Domain Chief',
                'description' => 'Main menu',
            ],
            [
                'label'       => 'Profile',
                'href'        => '/account/profile',
                'icon'        => 'fad fa-user-circle',
                'category'    => 'Account Chief',
                'description' => 'Account',
            ],
            [
                'label'       => 'Profile',
                'href'        => '/account/profile',
                'icon'        => 'fad fa-user-circle',
                'category'    => 'Account Chief',
                'description' => 'Account',
            ],
            [
                'label'       => 'Switch to Acme team',
                'href'        => '/team/acme/switch',
                'icon'        => 'fad fa-arrow-right-arrow-left',
                'category'    => 'Domain Chief > Teams',
                'description' => 'Acme',
            ],
        ],
    ])->render();

    expect($html)
        ->toContain('data-chief-shell')
        ->toContain('Dashboard')
        ->toContain('Profile')
        ->toContain('Account Chief &gt; Account')
        ->toContain('Switch to Acme team')
        ->toContain('Domain Chief &gt; Teams &gt; Acme')
        ->toContain('Account Chief')
        ->toContain('Chief Tools &gt; Account Chief')
        ->toContain('x-data="chiefShell"')
        ->toContain('data-theme-update-url="')
        ->toContain('chief/ui/theme/__theme__')
        ->toContain('commandOrder(paletteQuery')
        ->toContain('remoteResults')
        ->toContain('data-command-palette-search-url="')
        ->toContain('chief/ui/commands/search')
        ->toContain("remoteLoading ? 'fa-spinner-third fa-spin' : 'fa-search'")
        ->toContain('result.icon_url')
        ->toContain('x-on:load="$el.classList.remove')
        ->toContain('No results found.')
        ->toContain('/icons/domainchief.svg')
        ->toContain('menuOpen = !menuOpen')
        ->toContain('Search or jump to...')
        ->toContain('All apps')
        ->not->toContain('Searching...')
        ->not->toContain('Close main menu')
        ->not->toContain('main-menu-items');

    expect($html)->toContain('href="https://account.chief.app/team/current"');

    preg_match('/href="https:\/\/account\.chief\.app\/team\/current"([^>]*)>/', $html, $appLinkMatches);

    expect($appLinkMatches[1] ?? '')->not->toContain('target=');

    expect(substr_count($html, 'data-shell-title="dashboard"'))->toBe(1)
        ->and(substr_count($html, 'data-shell-title="profile"'))->toBe(1);
});

test('the modern shell does not expose dynamic command search to guests', function () {
    if (!Route::has('chief.shell.commands.search')) {
        Route::get('chief/ui/commands/search', static fn (): array => [])->name('chief.shell.commands.search');
    }

    config([
        'chief.shell.variant' => 'modern',
    ]);

    $html = view('chief::partial.menu', [
        'logoRedirect' => '/',
        'menuItems'    => [],
    ])->render();

    expect($html)
        ->toContain('data-chief-shell')
        ->not->toContain('data-command-palette-search-url="')
        ->not->toContain('chief/ui/commands/search');
});

test('the modern shell renders the saved dark theme before javascript hydration', function () {
    request()->cookies->set('chief_shell_theme', 'dark');

    config([
        'chief.shell.variant' => 'modern',
    ]);

    $html = view('chief::partial.menu', [
        'logoRedirect' => '/',
        'menuItems'    => [],
    ])->render();

    expect($html)
        ->toContain('data-theme="dark"')
        ->toContain('data-theme-preference="dark"')
        ->toContain('dark');
});

test('the modern shell resolves system theme before rendering shell content', function () {
    request()->cookies->set('chief_shell_theme', 'system');

    config([
        'chief.shell.variant' => 'modern',
    ]);

    $html = view('chief::partial.menu', [
        'logoRedirect' => '/',
        'menuItems'    => [],
    ])->render();

    expect($html)
        ->toContain('data-theme-preference="system"')
        ->toContain("window.matchMedia('(prefers-color-scheme: dark)').matches")
        ->toContain('document.currentScript.parentElement');
});

test('the modern shell keeps minimal marketing menus full width', function () {
    config([
        'chief.shell.variant' => 'modern',
    ]);

    $html = view('chief::partial.menu', [
        'minimalMenu'  => true,
        'logoRedirect' => '/',
        'menuItems'    => [
            [
                'href' => '/pricing',
                'icon' => 'fad fa-money-bill-wave',
                'text' => 'Pricing',
            ],
        ],
    ])->render();

    expect($html)
        ->toContain('data-chief-shell')
        ->toContain('w-full')
        ->toContain('justify-center')
        ->not->toContain('container mx-auto max-w-7xl');
});

test('the modern shell can hide the account menu theme selector', function () {
    config([
        'chief.teams'                => false,
        'chief.shell.variant'        => 'modern',
        'chief.shell.theme_selector' => false,
    ]);

    auth()->setUser(new User([
        'name'  => 'Alex',
        'email' => 'alex@example.com',
    ]));
    request()->attributes->set('team_hint', (new Team())->forceFill([
        'name' => 'Current team',
        'slug' => 'current',
    ]));

    $html = view('chief::partial.menu', [
        'logoRedirect' => '/',
        'menuItems'    => [],
    ])->render();

    expect($html)
        ->toContain('data-chief-shell')
        ->not->toContain("themeButtonClasses('light')")
        ->not->toContain("setTheme('light')");
});
