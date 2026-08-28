<?php

use ChiefTools\SDK\Entities\User;
use Illuminate\Support\Facades\Crypt;
use ChiefTools\SDK\Socialite\ChiefUser;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Exceptions;
use ChiefTools\SDK\Socialite\ChiefProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config([
        'chief.base_url' => 'https://account.chief.test',
        'services.chief' => [
            'client_id'     => 'domainchief',
            'client_secret' => 'secret',
            'redirect'      => '/login/callback',
        ],
    ]);
});

it('keeps initial OAuth requests on ordinary state', function () {
    $response = $this->get('/login')
        ->assertRedirectContains('/login/oauth/authorize');

    parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

    expect(ChiefProvider::hasAuthenticationRetryState($query['state']))->toBeFalse();

    $response
        ->assertSessionHas('state', $query['state'])
        ->assertSessionHas('code_verifier');
});

it('retries an invalid OAuth state once before failing gracefully', function () {
    Exceptions::fake();
    Socialite::fake('chief', static fn () => throw new InvalidStateException);

    $retryResponse = $this->get('/login/callback?code=expired-code&state=expired-state')
        ->assertRedirectContains('/login/oauth/authorize');

    Exceptions::assertNothingReported();

    parse_str(parse_url($retryResponse->headers->get('Location'), PHP_URL_QUERY), $query);

    $retryState = $query['state'];

    expect(ChiefProvider::hasAuthenticationRetryState($retryState))->toBeTrue();

    $retryResponse
        ->assertSessionHas('state', $retryState)
        ->assertSessionHas('code_verifier');

    $this->flushSession();

    $failureResponse = $this->get('/login/callback?' . http_build_query([
        'code'  => 'expired-code',
        'state' => $retryState,
    ]));

    $failureResponse
        ->assertRedirect(url('/'))
        ->assertSessionHas('message', [
            'text' => 'Authentication failed, please try again!',
            'type' => 'warning',
        ]);

    Exceptions::assertReported(InvalidStateException::class);
    Exceptions::assertReportedCount(1);
});

it('does not trust malformed authentication retry states', function () {
    expect(ChiefProvider::hasAuthenticationRetryState(null))->toBeFalse()
        ->and(ChiefProvider::hasAuthenticationRetryState(['state']))->toBeFalse()
        ->and(ChiefProvider::hasAuthenticationRetryState('invalid'))->toBeFalse()
        ->and(ChiefProvider::hasAuthenticationRetryState(Crypt::encryptString('unrelated-state')))->toBeFalse();
});

it('preserves OAuth error descriptions', function () {
    $this->get('/login/callback?error=access_denied&error_description=The+user+denied+the+request.')
        ->assertRedirect(url('/'))
        ->assertSessionHas('message', [
            'text' => 'Authentication failed (The user denied the request.), please try again!',
            'type' => 'warning',
        ]);
});

it('rejects callbacks without application-accessible teams before creating a local user', function () {
    $remote = new ChiefUser([
        'id'              => '1d018668-266f-41b7-8718-3986228c9d3a',
        'name'            => 'Morgan Vale',
        'email'           => 'morgan@example.test',
        'timezone'        => 'Europe/Amsterdam',
        'avatar_hash'     => 'avatar42',
        'default_team_id' => null,
        'teams'           => [],
    ]);
    $remote->setToken('billing-only-access-token');

    $provider = $this->mock(ChiefProvider::class);
    $provider->shouldReceive('user')->once()->andReturn($remote);
    $provider->shouldReceive('revokeAccessToken')->once()->with('billing-only-access-token');

    Socialite::shouldReceive('driver')->with('chief')->andReturn($provider);

    $this->get('/login/callback?code=synthetic-code')
        ->assertRedirect(url('/'))
        ->assertSessionHas('message', [
            'text' => 'Authentication failed (Your account does not have access to applications.), please try again!',
            'type' => 'warning',
        ]);

    $this->assertGuest();

    expect(User::query()->doesntExist())->toBeTrue();
});
