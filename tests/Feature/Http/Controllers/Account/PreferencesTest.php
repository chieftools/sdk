<?php

use Tests\Fixtures\Models\User;
use Illuminate\Support\Facades\Route;
use ChiefTools\SDK\Http\Controllers\Account\Preferences;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config([
        'chief.preferences'           => [
            'synthetic_bypass' => ['Synthetic bypass', 'Skips a synthetic check.', 'key', false, 'security'],
        ],
        'chief.preference_categories' => [
            'security' => ['name' => 'Security'],
        ],
    ]);

    if (!Route::has('account.preferences')) {
        Route::middleware(['web', 'auth'])->group(static function (): void {
            Route::get('account/preferences', Preferences::class)->name('account.preferences');
            Route::post('account/preference/toggle', [Preferences::class, 'toggle'])->name('account.preferences.toggle');
        });

        app('router')->getRoutes()->refreshNameLookups();
    }

    User::$lockedPreferenceReasons = [];
});

it('refuses to change a locked preference with 403 and the reason', function () {
    $user = createTeamContextUser();
    createTeamContextTeam('amber-grove', $user);

    User::$lockedPreferenceReasons = ['synthetic_bypass' => 'A synthetic team keeps this off.'];

    $this->actingAs($user)
        ->withoutMiddleware(Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
        ->postJson(route('account.preferences.toggle'), ['identity' => 'preference:synthetic_bypass', 'state' => true])
        ->assertForbidden()
        ->assertExactJson(['message' => 'A synthetic team keeps this off.', 'state' => false]);

    expect($user->fresh()->getPreference('synthetic_bypass'))->toBeFalse();
});

it('changes a preference that is not locked', function () {
    $user = createTeamContextUser();
    createTeamContextTeam('amber-grove', $user);

    $this->actingAs($user)
        ->withoutMiddleware(Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
        ->postJson(route('account.preferences.toggle'), ['identity' => 'preference:synthetic_bypass', 'state' => true])
        ->assertOk()
        ->assertJsonPath('state', true);

    expect($user->fresh()->getPreference('synthetic_bypass'))->toBeTrue();
});

it('shows why a locked preference cannot be changed and disables its toggle', function () {
    app('view')->addLocation(dirname(__DIR__, 4) . '/Fixtures/views');

    User::$lockedPreferenceReasons = ['synthetic_bypass' => 'A synthetic team keeps this off.'];

    $html = view('chief::account.preferences', [
        'user'       => createTeamContextUser(),
        'categories' => User::getPreferencesByCategory(),
    ])->render();

    expect($html)
        ->toContain('data-preference-locked="synthetic_bypass"')
        ->toContain('A synthetic team keeps this off.')
        ->toContain('aria-disabled="true"')
        ->not->toContain("@click=\"toggle('synthetic_bypass')\"");
});
