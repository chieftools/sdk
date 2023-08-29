<?php

namespace ChiefTools\SDK\API\DTO;

use Illuminate\Contracts\Support\Arrayable;

class UsageReport implements Arrayable
{
    public function __construct(
        public readonly string $id,
        public readonly int $usage,
    ) {}

    public function toArray(): array
    {
        return [
            'id'    => $this->id,
            'value' => $this->usage,
        ];
    }
}
