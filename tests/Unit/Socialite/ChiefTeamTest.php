<?php

use ChiefTools\SDK\Socialite\ChiefTeam;
use ChiefTools\SDK\Enums\Team\MembershipRole;

function syntheticChiefTeamPayload(array $overrides = []): array
{
    return array_replace([
        'id'                        => 42,
        'slug'                      => 'synthetic-studio',
        'name'                      => 'Synthetic Studio',
        'timezone'                  => 'Europe/Amsterdam',
        'avatar_hash'               => null,
        'gravatar_email'            => null,
        'limits'                    => [],
        'plan_id'                   => null,
        'plan_discounted'           => null,
        'actionable_invoices_count' => 0,
    ], $overrides);
}

it('parses an explicit membership role', function () {
    $team = ChiefTeam::fromMembershipArray(syntheticChiefTeamPayload([
        'role' => 'member',
    ]));

    expect($team->role)->toBe(MembershipRole::MEMBER);
});

it('rejects membership payloads without a valid role', function (array $payload) {
    expect(fn () => ChiefTeam::fromMembershipArray($payload))
        ->toThrow(InvalidArgumentException::class, 'Chief team membership payloads must contain a valid role.');
})->with([
    'missing role' => fn () => syntheticChiefTeamPayload(),
    'unknown role' => fn () => syntheticChiefTeamPayload(['role' => 'observer']),
]);

it('returns the required membership role', function () {
    $team = ChiefTeam::fromMembershipArray(syntheticChiefTeamPayload([
        'role' => 'billing',
    ]));

    expect($team->membershipRole())->toBe(MembershipRole::BILLING);
});

it('allows team-only payloads to omit membership context', function () {
    $team = ChiefTeam::fromArray(syntheticChiefTeamPayload());

    expect($team->role)->toBeNull()
        ->and(fn () => $team->membershipRole())
        ->toThrow(LogicException::class, 'This Chief team does not contain membership context.');
});
