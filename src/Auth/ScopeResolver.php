<?php

namespace ChiefTools\SDK\Auth;

class ScopeResolver
{
    /**
     * Check if any of the granted scopes satisfy the required scope.
     *
     * Rules:
     * 1. Exact match
     * 2. Parent grants children (prefix walk): `domainchief` grants `domainchief:dns:read`
     * 3. Write implies read: `domainchief:dns:write` satisfies `domainchief:dns:read`
     * 4. Cross-cutting: `domainchief:read` covers `domainchief:dns:read`, `domainchief:contacts:read`, etc.
     *    And `domainchief:write` also covers all `:read` scopes (write implies read)
     * 5. Transitive: if a parent scope is satisfied, all its children are too
     */
    public static function satisfies(array $grantedScopes, string $requiredScope): bool
    {
        foreach ($grantedScopes as $granted) {
            if ($granted === $requiredScope) {
                return true;
            }

            // Parent grants children (prefix walk)
            if (str_starts_with($requiredScope, "{$granted}:")) {
                return true;
            }

            // Write implies read at the same level
            if (str_ends_with($requiredScope, ':read')) {
                $writeScope = substr($requiredScope, 0, -5) . ':write';

                if ($granted === $writeScope) {
                    return true;
                }
            }

            // Cross-cutting: app:read covers app:*:read, app:write covers app:*:write and app:*:read
            if (preg_match('/^([^:]+):(read|write)$/', $granted, $m)) {
                $appPrefix = $m[1];
                $action    = $m[2];

                if (str_starts_with($requiredScope, "{$appPrefix}:") && str_ends_with($requiredScope, ":{$action}")) {
                    return true;
                }

                // app:write also cross-cuts app:*:read (write implies read)
                if ($action === 'write' && str_starts_with($requiredScope, "{$appPrefix}:") && str_ends_with($requiredScope, ':read')) {
                    return true;
                }
            }
        }

        // Transitive: if the parent scope would be satisfied, so is the child (via prefix walk)
        $lastColon = strrpos($requiredScope, ':');

        if ($lastColon !== false) {
            return self::satisfies($grantedScopes, substr($requiredScope, 0, $lastColon));
        }

        return false;
    }
}
