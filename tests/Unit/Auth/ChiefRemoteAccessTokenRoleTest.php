<?php

use ChiefTools\SDK\Enums\Team\MembershipRole;
use ChiefTools\SDK\Auth\ChiefRemoteAccessToken;

it('retains the scoped team membership role', function () {
    $token = new ChiefRemoteAccessToken(
        id: 'synthetic-client',
        name: 'Synthetic integration',
        prefix: 'cta',
        scopes: ['synthetic:read'],
        userId: '00000000-0000-4000-8000-000000000123',
        teamId: 42,
        expiresAt: null,
        teamRole: MembershipRole::OWNER,
    );

    expect($token->teamRole)->toBe(MembershipRole::OWNER);
});
