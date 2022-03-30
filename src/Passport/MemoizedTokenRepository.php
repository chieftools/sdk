<?php

namespace IronGate\Chief\Passport;

use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;

class MemoizedTokenRepository extends TokenRepository
{
    private static array $cache = [];

    public function find($id): ?Token
    {
        if (!isset(self::$cache[$id])) {
            self::$cache[$id] = parent::find($id);
        }

        return self::$cache[$id];
    }

    public function findForUser($id, $userId): ?Token
    {
        $token = $this->find($id);

        return $token !== null && $token->user_id === $userId
            ? $token
            : null;
    }

    public function save(Token $token): void
    {
        parent::save($token);

        self::$cache[$token->id] = $token;
    }

    public function revokeAccessToken($id): bool
    {
        unset(self::$cache[$id]);

        return parent::revokeAccessToken($id);
    }
}
