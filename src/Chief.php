<?php

namespace ChiefTools\SDK;

use Closure;
use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\Entities\User;

final class Chief
{
    private static bool $runsMigrations = true;

    /** @param class-string<\ChiefTools\SDK\Entities\User> $userModel */
    private static string $userModel = User::class;

    /** @param class-string<\ChiefTools\SDK\Entities\Team> $teamModel */
    private static string $teamModel = Team::class;

    private static ?string $afterUserUpdateJob = null;

    private static ?string $afterTeamUpdateJob = null;

    private static ?Closure $shouldRenderSupportWidgetResolver = null;

    private static ?Closure $shouldRenderAnalyticsTrackerResolver = null;

    private static ?Closure $validationExceptionJsonResponseHandler = null;

    private static ?Closure $authenticationExceptionJsonResponseHandler = null;

    /** @param class-string<\ChiefTools\SDK\Entities\User> $userModel */
    public static function useUserModel(string $userModel): void
    {
        self::$userModel = $userModel;
    }

    /** @return class-string<\ChiefTools\SDK\Entities\User> */
    public static function userModel(): string
    {
        return self::$userModel;
    }

    /** @param class-string<\ChiefTools\SDK\Entities\Team> $teamModel */
    public static function useTeamModel(string $teamModel): void
    {
        self::$teamModel = $teamModel;
    }

    /** @return class-string<\ChiefTools\SDK\Entities\Team> */
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
        $default = static fn () => authenticated_user()?->getPreference('enable_support_widget', true) ?? true;

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

    public static function getValidationExceptionJsonResponseHandler(): ?Closure
    {
        return self::$validationExceptionJsonResponseHandler;
    }

    public static function registerValidationExceptionJsonResponseHandler(?Closure $handler): void
    {
        self::$validationExceptionJsonResponseHandler = $handler;
    }

    public static function getAuthenticationExceptionJsonResponseHandler(): ?Closure
    {
        return self::$authenticationExceptionJsonResponseHandler;
    }

    public static function registerAuthenticationExceptionJsonResponseHandler(?Closure $handler): void
    {
        self::$authenticationExceptionJsonResponseHandler = $handler;
    }
}
