<?php

namespace ChiefTools\SDK\Auth;

use Carbon\Carbon;

readonly class ChiefRemoteAccessToken
{
    public function __construct(
        public array $scopes,
        public string $userId,
        public ?int $teamId,
        public ?Carbon $expiresAt,
    ) {}
}
