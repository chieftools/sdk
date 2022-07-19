<?php

namespace IronGate\Chief\Enums;

use IronGate\Chief\Helpers\RandomToken;

enum TokenPrefix: string
{
    case TEAM_ACCESS_TOKEN     = 'ctt';
    case PERSONAL_ACCESS_TOKEN = 'ctp';

    public function isForToken(RandomToken $token): bool
    {
        return $token->prefix === $this->value;
    }
}
