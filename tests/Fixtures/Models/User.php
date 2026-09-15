<?php

namespace Tests\Fixtures\Models;

use ChiefTools\SDK\Socialite\ChiefUser;
use ChiefTools\SDK\Entities\User as BaseUser;

class User extends BaseUser
{
    /** @var list<string> */
    public static array $updatedChiefIds = [];

    public function updateFromRemote(ChiefUser $remote): void
    {
        parent::updateFromRemote($remote);

        self::$updatedChiefIds[] = $remote->getId();
    }
}
