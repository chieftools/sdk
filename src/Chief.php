<?php

namespace ChiefTools\SDK;

use ChiefTools\SDK\Entities\Team;

class Chief
{
    private static bool $runsMigrations = true;

    private static string $teamModel = Team::class;

    public static function useTeamModel(string $teamModel): void
    {
        self::$teamModel = $teamModel;
    }

    public static function teamModel(): string
    {
        return self::$teamModel;
    }

    public static function ignoreMigrations(): void
    {
        self::$runsMigrations = false;
    }

    public static function runsMigrations(): bool
    {
        return self::$runsMigrations;
    }
}
