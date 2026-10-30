<?php

use ChiefTools\SDK\Auth\ChiefRemoteAccessToken;

function tokenWithAuthorizationDetails(array $authorizationDetails): ChiefRemoteAccessToken
{
    return new ChiefRemoteAccessToken(
        id: 'synthetic-token',
        name: 'Synthetic token',
        prefix: 'ctt',
        scopes: ['synthetic:records:write'],
        userId: null,
        teamId: 42,
        expiresAt: null,
        authorizationDetails: $authorizationDetails,
    );
}

it('allows every resource when the token has no authorization details', function () {
    $token = tokenWithAuthorizationDetails([]);

    expect($token->isRestrictedFor('synthetic'))->toBeFalse()
        ->and($token->allows('synthetic:zone', 'fern-valley.example'))->toBeTrue();
});

it('allows only the listed resources of a restricted app', function () {
    $token = tokenWithAuthorizationDetails([
        ['type' => 'synthetic:zone', 'identifier' => 'fern-valley.example'],
    ]);

    expect($token->isRestrictedFor('synthetic'))->toBeTrue()
        ->and($token->allows('synthetic:zone', 'fern-valley.example'))->toBeTrue()
        ->and($token->allows('synthetic:zone', 'glen-harbor.example'))->toBeFalse();
});

it('allows no resources of an unlisted type once the app is restricted', function () {
    $token = tokenWithAuthorizationDetails([
        ['type' => 'synthetic:zone', 'identifier' => 'fern-valley.example'],
    ]);

    expect($token->allowedIdentifiers('synthetic:contact'))->toBe([])
        ->and($token->allows('synthetic:contact', 'CH-1'))->toBeFalse();
});

it('does not restrict other apps', function () {
    $token = tokenWithAuthorizationDetails([
        ['type' => 'synthetic:zone', 'identifier' => 'fern-valley.example'],
    ]);

    expect($token->isRestrictedFor('other'))->toBeFalse()
        ->and($token->allows('other:zone', 'glen-harbor.example'))->toBeTrue();
});

it('lists the unique allowed identifiers of a type', function () {
    $token = tokenWithAuthorizationDetails([
        ['type' => 'synthetic:zone', 'identifier' => 'fern-valley.example'],
        ['type' => 'synthetic:contact', 'identifier' => 'CH-1'],
        ['type' => 'synthetic:zone', 'identifier' => 'glen-harbor.example'],
        ['type' => 'synthetic:zone', 'identifier' => 'fern-valley.example'],
    ]);

    expect($token->allowedIdentifiers('synthetic:zone'))->toBe(['fern-valley.example', 'glen-harbor.example']);
});

it('drops malformed authorization details from a remote response', function (mixed $details, array $expected) {
    expect(ChiefRemoteAccessToken::normalizeAuthorizationDetails($details))->toBe($expected);
})->with([
    'not an array'       => [null, []],
    'missing identifier' => [[['type' => 'synthetic:zone']], []],
    'non-string type'    => [[['type' => 1, 'identifier' => 'fern-valley.example']], []],
    'extra fields'       => [
        [['type' => 'synthetic:zone', 'identifier' => 'fern-valley.example', 'actions' => ['read']]],
        [['type' => 'synthetic:zone', 'identifier' => 'fern-valley.example']],
    ],
]);
