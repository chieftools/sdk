<?php

namespace ChiefTools\SDK\Enums;

use Stayallive\RandomTokens\RandomToken;

enum TokenPrefix: string
{
    case TEAM_ACCESS_TOKEN     = 'ctt';
    case PERSONAL_ACCESS_TOKEN = 'ctp';
    case OAUTH_ACCESS_TOKEN    = 'cto';
    case OAUTH_REFRESH_TOKEN   = 'ctr';

    public function isForToken(RandomToken $token): bool
    {
        return $token->prefix === $this->value;
    }
}
