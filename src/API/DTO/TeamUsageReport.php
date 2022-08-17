<?php

namespace ChiefTools\SDK\API\DTO;

use Illuminate\Contracts\Support\Arrayable;

class TeamUsageReport implements Arrayable
{
    /**
     * @param array<int, \ChiefTools\SDK\API\DTO\UsageReport> $usages
     */
    public function __construct(
        public readonly string $team,
        public readonly array $usages,
    ) {
    }

    public function toArray(): array
    {
        return [
            'team'   => $this->team,
            'usages' => collect($this->usages)->toArray(),
        ];
    }
}
