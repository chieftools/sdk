<?php

namespace ChiefTools\SDK\Auth;

use Carbon\Carbon;

readonly class ChiefRemoteAccessToken
{
    private array $flippedScopes;

    public function __construct(
        public array $scopes,
        public ?string $userId,
        public ?int $teamId,
        public ?Carbon $expiresAt,
        public ?string $plainTextToken = null,
    ) {
        $this->flippedScopes = array_flip($this->scopes);
    }

    /**
     * Determine if the token has the given scope.
     */
    public function can(string $scopeToTest): bool
    {
        $appId = config('chief.id');

        // A token with the scope of the application ID (without suffix) has all the application specific scopes
        if (str_starts_with($scopeToTest, "{$appId}:") && $this->hasScope($appId)) {
            return true;
        }

        return $this->hasScope($scopeToTest);
    }

    /**
     * Determine if the token is missing the given scope.
     */
    public function cant(string $scope): bool
    {
        return !$this->can($scope);
    }

    /**
     * Determine if the token has the given scope.
     */
    public function hasScope(string $scope): bool
    {
        return array_key_exists($scope, $this->flippedScopes);
    }
}
