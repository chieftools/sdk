<?php

namespace ChiefTools\SDK\Auth;

use Carbon\Carbon;
use ChiefTools\SDK\Enums\Team\MembershipRole;

readonly class ChiefRemoteAccessToken
{
    /**
     * @param list<string>                                  $scopes
     * @param list<array{type: string, identifier: string}> $authorizationDetails
     */
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
        /** The user's current role on the scoped team. */
        public ?MembershipRole $teamRole = null,
        /** The protected resource this token is intended for. */
        public ?string $audience = null,
        /**
         * The resources this token is limited to, as RFC 9396 authorization details.
         *
         * Each detail has a `type` prefixed with the app ID (e.g. `domainchief:domain`) and the `identifier` of an allowed resource.
         * A token with any detail for an app is restricted to the listed resources of that app.
         */
        public array $authorizationDetails = [],
    ) {}

    /**
     * Normalize authorization details from a remote response, dropping malformed entries.
     *
     * @return list<array{type: string, identifier: string}>
     */
    public static function normalizeAuthorizationDetails(mixed $details): array
    {
        if (!is_array($details)) {
            return [];
        }

        $normalized = [];

        foreach ($details as $detail) {
            if (!is_array($detail) || !is_string($detail['type'] ?? null) || !is_string($detail['identifier'] ?? null)) {
                continue;
            }

            $normalized[] = ['type' => $detail['type'], 'identifier' => $detail['identifier']];
        }

        return $normalized;
    }

    /** Determine if the token has the given scope. */
    public function can(string $scopeToTest): bool
    {
        return ScopeResolver::satisfies($this->scopes, $scopeToTest);
    }

    /** Determine if the token is missing the given scope. */
    public function cant(string $scope): bool
    {
        return !$this->can($scope);
    }

    /** Determine if the token has the given scope (exact match). */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }

    /** Determine if the token has any scope of the given app, the app scope itself or any of its children. */
    public function hasAnyScopeFor(string $appId): bool
    {
        return array_any($this->scopes, static fn (string $scope): bool => $scope === $appId || str_starts_with($scope, "{$appId}:"));
    }

    /** Determine if the token is limited to specific resources of the given app. */
    public function isRestrictedFor(string $appId): bool
    {
        return array_any($this->authorizationDetails, static fn (array $detail): bool => str_starts_with($detail['type'], "{$appId}:"));
    }

    /**
     * The identifiers of the allowed resources of the given type.
     *
     * @return list<string>
     */
    public function allowedIdentifiers(string $type): array
    {
        return array_values(array_unique(array_column(
            array_filter($this->authorizationDetails, static fn (array $detail): bool => $detail['type'] === $type),
            'identifier',
        )));
    }

    /**
     * Determine if the token allows access to the given resource.
     *
     * Always true when the token is not restricted for the app of the resource type.
     */
    public function allows(string $type, string $identifier): bool
    {
        $appId = strstr($type, ':', true);

        if ($appId === false || !$this->isRestrictedFor($appId)) {
            return true;
        }

        return in_array($identifier, $this->allowedIdentifiers($type), true);
    }

    /** Determine if the token is intended for the given audience. */
    public function isForAudience(string $audience): bool
    {
        return $this->audience !== null && hash_equals($this->audience, $audience);
    }
}
