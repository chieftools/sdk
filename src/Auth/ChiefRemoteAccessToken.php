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
     *
     * Supports hierarchical scope resolution:
     * - Parent walking: a parent scope grants all child scopes (e.g., "app" covers "app:domains:read")
     * - Write-implies-read: a ":write" scope also satisfies the equivalent ":read" scope
     * - Cross-cutting actions: "app:read" covers "app:{resource}:read" for any resource
     */
    public function can(string $scopeToTest): bool
    {
        // Fast path: exact match
        if ($this->hasScope($scopeToTest)) {
            return true;
        }

        // Hierarchical: any parent scope grants all child scopes
        // Uses can() recursively so write-implies-read and cross-cutting
        // are evaluated at each parent level
        $parent = $scopeToTest;

        while (($pos = strrpos($parent, ':')) !== false) {
            $parent = substr($parent, 0, $pos);

            if ($this->can($parent)) {
                return true;
            }
        }

        $parts = explode(':', $scopeToTest);
        $appId = config('chief.id');

        // Write-implies-read: :write at same prefix satisfies :read
        if (end($parts) === 'read') {
            $writeEquivalent = substr($scopeToTest, 0, -4) . 'write';

            if ($this->can($writeEquivalent)) {
                return true;
            }
        }

        // Cross-cutting action scopes: app:action covers app:resource:action
        // Strips the first segment after the app ID and recurses
        // e.g., app:domains:read:availability → app:read:availability
        if (count($parts) > 2 && $parts[0] === $appId) {
            $shortened = $appId . ':' . implode(':', array_slice($parts, 2));

            if ($this->can($shortened)) {
                return true;
            }
        }

        return false;
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
