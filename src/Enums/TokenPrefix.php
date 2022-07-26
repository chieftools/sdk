<?php

namespace ChiefTools\SDK\Enums;

use ChiefTools\SDK\Helpers\RandomToken;

enum TokenPrefix: string
{
    case TEAM_ACCESS_TOKEN     = 'ctt';
    case PERSONAL_ACCESS_TOKEN = 'ctp';

    public function isForToken(RandomToken $token): bool
    {
        return $token->prefix === $this->value;
    }
}
