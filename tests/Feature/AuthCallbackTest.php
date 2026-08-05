<?php

use Illuminate\Support\Facades\Crypt;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Exceptions;
use ChiefTools\SDK\Socialite\ChiefProvider;
use Laravel\Socialite\Two\InvalidStateException;

beforeEach(function () {
    config([
        'app.key'        => 'base64:oE72uRMtvwHlVTVBthR+K3FBDmSqNXTevcEU2LtLqrw=',
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
