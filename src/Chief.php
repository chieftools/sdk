<?php

namespace ChiefTools\SDK;

use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\Entities\User;

final class Chief
{
    private static bool $runsMigrations = true;

    private static string $teamModel = Team::class;

    private static ?string $afterUserUpdateJob = null;

    private static ?string $afterTeamUpdateJob = null;

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

    public static function registerAfterUserUpdateJob(string $jobClass): void
    {
        self::$afterUserUpdateJob = $jobClass;
    }

    public static function dispatchAfterUserUpdateJob(User $user): void
    {
        if (self::$afterUserUpdateJob === null) {
            return;
        }

        dispatch(new self::$afterUserUpdateJob($user));
    }

    public static function registerAfterTeamUpdateJob(string $jobClass): void
    {
        self::$afterTeamUpdateJob = $jobClass;
    }

    public static function dispatchAfterTeamUpdateJob(Team $team): void
    {
        if (self::$afterTeamUpdateJob === null) {
            return;
        }

        dispatch(new self::$afterTeamUpdateJob($team));
    }
}
