<?php

use ChiefTools\SDK\Auth\ChiefRemoteAccessToken;

it('matches the exact configured audience', function () {
    $token = new ChiefRemoteAccessToken(
        id: 'synthetic-client',
        name: 'Synthetic integration',
        prefix: 'cto',
        scopes: ['synthetic:read'],
        userId: '00000000-0000-4000-8000-000000000456',
        teamId: 42,
        expiresAt: null,
        audience: 'https://resource.service.invalid/mcp',
    );

    expect($token->isForAudience('https://resource.service.invalid/mcp'))->toBeTrue()
        ->and($token->isForAudience('https://other.service.invalid/mcp'))->toBeFalse();
});

it('does not match an audience when the token is not resource bound', function () {
    $token = new ChiefRemoteAccessToken(
        id: 'synthetic-client',
        name: 'Synthetic integration',
        prefix: 'cto',
        scopes: ['synthetic:read'],
        userId: null,
        teamId: null,
        expiresAt: null,
    );

    expect($token->isForAudience('https://resource.service.invalid/mcp'))->toBeFalse();
});
