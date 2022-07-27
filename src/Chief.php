<?php

namespace ChiefTools\SDK;

use ChiefTools\SDK\Entities\Team;

class Chief
{
    private static string $teamModel = Team::class;

    public static function useTeamModel(string $teamModel): void
    {
        self::$teamModel = $teamModel;
    }

    public static function teamModel(): string
    {
        return self::$teamModel;
    }
}
