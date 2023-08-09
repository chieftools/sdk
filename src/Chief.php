<?php

namespace ChiefTools\SDK;

use Closure;
use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\Entities\User;

final class Chief
{
    private static bool $runsMigrations = true;

    private static string $teamModel = Team::class;

    private static ?string $afterUserUpdateJob = null;

    private static ?string $afterTeamUpdateJob = null;

    private static ?Closure $shouldRenderSupportWidgetResolver = null;

    private static ?Closure $shouldRenderAnalyticsTrackerResolver = null;

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

    public static function shouldRenderSupportWidget(): bool
    {
        $default = static function () {
            if (config('services.plain.app_key') === null) {
                return false;
            }

            /** @var \ChiefTools\SDK\Entities\User|null $user */
            $user = auth()->user();

            return $user?->getPreference('enable_support_widget', true) ?? true;
        };

        return value(self::$shouldRenderSupportWidgetResolver ?? $default);
    }

    public static function shouldRenderAnalyticsTracker(): bool
    {
        $default = static function () {
            return config('chief.analytics.fathom.site') !== null;
        };

        return value(self::$shouldRenderAnalyticsTrackerResolver ?? $default);
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

    public static function disableExternalScriptRendering(): void
    {
        self::registerShouldRenderSupportWidgetResolver(static fn () => false);
        self::registerShouldRenderAnalyticsTrackerResolver(static fn () => false);
    }

    public static function registerShouldRenderSupportWidgetResolver(?Closure $resolver): void
    {
        self::$shouldRenderSupportWidgetResolver = $resolver;
    }

    public static function registerShouldRenderAnalyticsTrackerResolver(?Closure $resolver): void
    {
        self::$shouldRenderAnalyticsTrackerResolver = $resolver;
    }
}
