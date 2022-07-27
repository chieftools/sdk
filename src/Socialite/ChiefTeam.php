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
        public readonly array $limits,
    ) {
    }

    public static function fromArray(array $team): self
    {
        return new self(
            id: $team['id'],
            slug: $team['slug'],
            name: $team['name'],
            limits: $team['limits'],
        );
    }
}