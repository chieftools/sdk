<?php

namespace ChiefTools\SDK\Webhook;

enum WebhookEvent: string
{
    case TEAM_UPDATED   = 'team_updated';
    case TEAM_DESTROYED = 'team_destroyed';

    case ACCOUNT_CLOSED  = 'account_closed';
    case ACCOUNT_UPDATED = 'account_updated';

    case TOKEN_DESTROYED = 'token_destroyed';

    case CHECKOUT_SESSION_EXPIRED   = 'checkout_session_expired';
    case CHECKOUT_SESSION_COMPLETED = 'checkout_session_completed';
}
