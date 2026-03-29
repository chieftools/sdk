<?php

namespace ChiefTools\SDK\Auth;

use Carbon\Carbon;

readonly class ChiefRemoteAccessToken
{
    public function __construct(
        public array $scopes,
        public ?string $userId,
        public ?int $teamId,
        public ?Carbon $expiresAt,
        public ?string $plainTextToken = null,
    ) {}

    /**
     * Determine if the token has the given scope.
     */
    public function can(string $scopeToTest): bool
    {
        return ScopeResolver::satisfies($this->scopes, $scopeToTest);
    }

    /**
     * Determine if the token is missing the given scope.
     */
    public function cant(string $scope): bool
    {
        return !$this->can($scope);
    }

    /**
     * Determine if the token has the given scope (exact match).
     */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }
}
