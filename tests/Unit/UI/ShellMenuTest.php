<?php

use Tests\TestCase;
use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\Entities\User;
use Illuminate\Support\Facades\Blade;
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
        'chief.id'                              => 'domainchief',
        'chief.shell.variant'                   => 'modern',
        'chief.shell.command_palette_providers' => ['domainchief.domains'],
        'chief.brand.icon'                      => 'fa-globe',
        'chief.brand.color'                     => '#3498db',
    ]);

    auth()->setUser(new User([
        'name'  => 'Alex',
        'email' => 'alex@example.com',
    ]));
    request()->attributes->set('team_hint', (new Team)->forceFill([
        'name' => 'Current team',
        'slug' => 'current',
    ]));

    $html = view('chief::partial.menu', [
        'logoRedirect'  => '/',
        'shellTool'     => [
            'name'         => 'Domain Chief',
            'logoColorUrl' => '/icons/domainchief.svg',
        ],
        'menuItems'     => [
            [
                'href'   => '/dashboard',
                'icon'   => 'fad fa-dashboard',
                'text'   => 'Dashboard',
                'active' => true,
            ],
        ],
        'shellApps'     => [
            [
                'id'           => 'accountchief',
                'name'         => 'Account Chief',
                'icon'         => 'fa-toolbox',
                'brandIcon'    => 'fa-github',
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
        ->toContain('Chief Tools &gt; Account Chief')
        ->toContain('data-command-palette-search-url="')
        ->toContain('chief/ui/commands/search')
        ->toContain('No results found.')
        ->toContain('src="/icons/domainchief.svg"')
        ->toContain('Search or jump to...')
        ->toContain('All apps')
        ->not->toContain('main-menu-items');

    expect($html)->toContain('href="https://account.chief.app/team/current"');

    preg_match('/href="https:\/\/account\.chief\.app\/team\/current"([^>]*)>/', $html, $appLinkMatches);

    expect($appLinkMatches[1] ?? '')->not->toContain('target=');

    expect(substr_count($html, 'data-shell-title="dashboard"'))->toBe(1)
        ->and(substr_count($html, 'data-shell-title="profile"'))->toBe(1);
});

test('the modern shell does not render a dynamic command search url without providers', function () {
    if (!Route::has('chief.shell.commands.search')) {
        Route::get('chief/ui/commands/search', static fn (): array => [])->name('chief.shell.commands.search');
    }

    config([
        'chief.teams'                           => false,
        'chief.shell.variant'                   => 'modern',
        'chief.shell.command_palette_providers' => [],
    ]);

    auth()->setUser(new User([
        'name'  => 'Alex',
        'email' => 'alex@example.com',
    ]));

    $html = view('chief::partial.menu', [
        'logoRedirect' => '/',
        'menuItems'    => [],
    ])->render();

    expect($html)
        ->not->toContain('data-command-palette-search-url="')
        ->not->toContain('chief/ui/commands/search');
});

test('the modern shell renders current-page commands as a separate palette context', function () {
    config([
        'chief.shell.variant' => 'modern',
    ]);

    $html = view('chief::partial.menu', [
        'logoRedirect'        => '/',
        'menuItems'           => [],
        'shellCommandContext' => [
            'label'    => 'violetforge.test',
            'commands' => [
                [
                    'label'       => 'DNS records',
                    'href'        => '/domains/violetforge.test/records',
                    'category'    => 'Domain Chief > Domains',
                    'description' => 'violetforge.test',
                    'keywords'    => ['dns', 'zone'],
                ],
            ],
        ],
    ])->render();

    expect($html)
        ->toContain('For violetforge.test')
        ->toContain('All commands')
        ->toContain('href="/domains/violetforge.test/records"')
        ->toContain('Back to previous command results')
        ->toContain('Show destinations for')
        ->toContain('data-shell-command-browse');
});

test('the modern shell renders the configured brand icon when no logo is configured', function () {
    config([
        'chief.id'              => 'billdo',
        'chief.shell.variant'   => 'modern',
        'chief.brand.icon'      => 'fa-money-bill',
        'chief.brand.brandIcon' => 'fa-digital-ocean',
        'chief.brand.color'     => '#3d82f7',
        'chief.brand.logoUrl'   => null,
    ]);

    $html = view('chief::partial.menu', [
        'logoRedirect' => '/',
        'shellTool'    => [
            'name' => 'Billdo',
        ],
    ])->render();

    expect($html)
        ->toContain('fa-money-bill')
        ->toContain('fa-digital-ocean');
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
        ->not->toContain('data-command-palette-search-url="')
        ->not->toContain('chief/ui/commands/search');
});

test('the modern shell renders translated public chrome and extension actions', function () {
    config([
        'chief.shell.variant'         => 'modern',
        'chief.shell.command_palette' => true,
    ]);
    app()->setLocale('nl');

    $html = Blade::render(<<<'BLADE'
        @push('chief.shell.actions')
            <span data-test-language-action>NL</span>
        @endpush
        @include('chief::partial.menu', ['logoRedirect' => '/', 'menuItems' => []])
        BLADE);

    expect($html)
        ->toContain('data-test-language-action')
        ->toContain('Zoeken of snel navigeren...')
        ->toContain('Hoofdmenu openen of sluiten');
});

test('the modern shell renders a guest theme selector when theme route exists', function () {
    if (!Route::has('chief.shell.theme')) {
        Route::get('chief/ui/theme/{theme}', static fn (): array => [])->name('chief.shell.theme');
    }

    config([
        'chief.shell.variant'        => 'modern',
        'chief.shell.theme_selector' => true,
    ]);

    $html = view('chief::partial.menu', [
        'logoRedirect' => '/',
        'menuItems'    => [],
    ])->render();

    $text = strip_tags($html);

    expect($html)
        ->toContain('aria-label="Theme"')
        ->toContain('chief/ui/theme/__theme__');

    expect($text)
        ->toContain('Light')
        ->toContain('Dark')
        ->toContain('System');
});

test('the shell theme route stores guest preferences without authentication', function () {
    $this->getJson('/api/chief/ui/theme/dark')
        ->assertOk()
        ->assertJson(['theme' => 'dark'])
        ->assertPlainCookie('chief_shell_theme', 'dark');
});

test('the modern shell can hide the guest theme selector', function () {
    if (!Route::has('chief.shell.theme')) {
        Route::get('chief/ui/theme/{theme}', static fn (): array => [])->name('chief.shell.theme');
    }

    config([
        'chief.shell.variant'        => 'modern',
        'chief.shell.theme_selector' => false,
    ]);

    $html = view('chief::partial.menu', [
        'logoRedirect' => '/',
        'menuItems'    => [],
    ])->render();

    $text = strip_tags($html);

    expect($html)
        ->not->toContain('aria-label="Theme"');

    expect($text)
        ->not->toContain('Switch to light theme')
        ->not->toContain('Switch to dark theme')
        ->not->toContain('Use system theme');
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
        ->toContain('data-theme-preference="dark"');
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

    $beforeShellContent = explode('<header', $html, 2)[0];

    expect($html)->toContain('data-theme-preference="system"');

    expect($beforeShellContent)
        ->toContain("window.matchMedia('(prefers-color-scheme: dark)').matches");
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
    request()->attributes->set('team_hint', (new Team)->forceFill([
        'name' => 'Current team',
        'slug' => 'current',
    ]));

    $html = view('chief::partial.menu', [
        'logoRedirect' => '/',
        'menuItems'    => [],
    ])->render();

    $text = strip_tags($html);

    expect($text)
        ->toContain('Open account menu')
        ->not->toContain('Switch to light theme')
        ->not->toContain('Switch to dark theme')
        ->not->toContain('Use system theme');
});
