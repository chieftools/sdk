<?php

namespace ChiefTools\SDK\Enums\Team;

enum MembershipRole: string
{
    case OWNER   = 'owner';
    case MEMBER  = 'member';
    case BILLING = 'billing';
}
