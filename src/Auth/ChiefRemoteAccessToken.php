<?php

namespace ChiefTools\SDK\Auth;

use Carbon\Carbon;

readonly class ChiefRemoteAccessToken
{
    /** @param list<string> $scopes */
    public function __construct(
        /** Contains the token ID or client ID the token belongs to. Not guaranteed to be unique. */
        public string $id,
        /** The name of the token in case of a personal/team token or the name of the client for OAuth tokens. */
        public string $name,
        /** The token prefix which identifies the type of token. */
        public string $prefix,
        /** The scopes granted to this token. */
        public array $scopes,
        /** The user UUID the token is scoped to. */
        public ?string $userId,
        /** The team ID the token is scoped to. */
        public ?int $teamId,
        /** Datetime on which the token expires. */
        public ?Carbon $expiresAt,
        /** The actual token string if available. */
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
