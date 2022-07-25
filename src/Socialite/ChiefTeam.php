<?php

namespace IronGate\Chief\Socialite;

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
}
