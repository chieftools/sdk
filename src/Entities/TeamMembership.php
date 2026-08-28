<?php

namespace ChiefTools\SDK\Entities;

use ChiefTools\SDK\Enums\Team\MembershipRole;

/**
 * @property \ChiefTools\SDK\Enums\Team\MembershipRole $role
 */
class TeamMembership extends PivotEntity
{
    protected $table = 'team_user';
    protected $casts = [
        'role' => MembershipRole::class,
    ];
}
