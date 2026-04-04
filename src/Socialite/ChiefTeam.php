<?php

namespace ChiefTools\SDK\Socialite;

class ChiefTeam
{
    /**
     * @param array<string, int> $limits
     */
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
    ) {}

    public static function fromArray(array $team): self
    {
        return new self(
            id: $team['id'],
            slug: $team['slug'],
            name: $team['name'],
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
