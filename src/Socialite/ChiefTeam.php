<?php

namespace ChiefTools\SDK\Socialite;

use LogicException;
use InvalidArgumentException;
use ChiefTools\SDK\Enums\Team\MembershipRole;

class ChiefTeam
{
    /** @param array<string, int> $limits */
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $name,
        public readonly string $timezone,
        public readonly ?string $avatarHash,
        public readonly ?string $gravatarEmail,
        public readonly array $limits,
        public readonly ?string $planId,
        public readonly ?bool $planDiscounted,
        public readonly int $actionableInvoicesCount,
        public readonly ?MembershipRole $role = null,
    ) {}

    public static function fromArray(array $team): self
    {
        return self::make($team);
    }

    public static function fromMembershipArray(array $team): self
    {
        $role = MembershipRole::tryFrom((string)($team['role'] ?? ''))
            ?? throw new InvalidArgumentException('Chief team membership payloads must contain a valid role.');

        return self::make($team, $role);
    }

    public function membershipRole(): MembershipRole
    {
        return $this->role
            ?? throw new LogicException('This Chief team does not contain membership context.');
    }

    private static function make(array $team, ?MembershipRole $role = null): self
    {
        return new self(
            id: $team['id'],
            slug: $team['slug'],
            name: $team['name'],
            role: $role,
            limits: $team['limits'],
            planId: $team['plan_id'] ?? null,
            timezone: $team['timezone'],
            avatarHash: $team['avatar_hash'] ?? null,
            gravatarEmail: $team['gravatar_email'] ?? null,
            planDiscounted: $team['plan_discounted'] ?? null,
            actionableInvoicesCount: $team['actionable_invoices_count'] ?? 0,
        );
    }
}
