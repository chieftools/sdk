<?php

namespace ChiefTools\SDK\Entities;

use ChiefTools\SDK\Enums\Team\MembershipRole;

/**
 * @property \ChiefTools\SDK\Enums\Team\MembershipRole $role
 * @property bool                                      $access_restricted
 */
class TeamMembership extends PivotEntity
{
    protected $table = 'team_user';
    protected $casts = [
        'role'              => MembershipRole::class,
        'access_restricted' => 'boolean',
    ];
}
