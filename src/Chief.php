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

    /** @var \Closure(): bool|null */
    private static ?Closure $shouldRenderSupportWidgetResolver = null;

    /** @var \Closure(): bool|null */
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
        $default = static fn (): bool => authenticated_user()?->getPreference('enable_support_widget', true) ?? true;

        return (self::$shouldRenderSupportWidgetResolver ?? $default)();
    }

    public static function shouldRenderAnalyticsTracker(): bool
    {
        $default = static fn (): bool => config('chief.analytics.fathom.site') !== null;

        return (self::$shouldRenderAnalyticsTrackerResolver ?? $default)();
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

    /**
     * Read the visitor's theme preference from the shell cookie.
     *
     * Returns one of `'light'`, `'dark'`, or `'system'`. When the cookie is
     * missing or invalid, falls back to `config('chief.shell.theme', 'system')`
     * — defaulting to `'system'` so first-time visitors get OS-respecting
     * dark/light without an explicit pick.
     */
    public static function themePreference(): string
    {
        $cookieName = config('chief.shell.theme_cookie', 'chief_shell_theme');
        $default    = config('chief.shell.theme', 'system');
        $default    = in_array($default, ['light', 'dark', 'system'], true) ? $default : 'system';

        $preference = request()->cookie($cookieName, $default);

        return in_array($preference, ['light', 'dark', 'system'], true) ? $preference : $default;
    }

    /**
     * Resolve the visitor's theme preference to a concrete `'light'` or `'dark'`.
     *
     * `'system'` resolves to `'light'` since OS preference cannot be detected
     * server-side; the shell's bootstrap script flips the document theme on the
     * client when needed.
     */
    public static function theme(): string
    {
        return self::themePreference() === 'dark' ? 'dark' : 'light';
    }

    /**
     * Get the explicit theme choice, or `null` when the user wants OS-detected.
     *
     * Returns `'light'` or `'dark'` for explicit picks, or `null` when the
     * preference is `'system'`. Useful for embeds and external widgets that
     * auto-detect via `prefers-color-scheme` when no theme attr is supplied.
     */
    public static function explicitTheme(): ?string
    {
        $preference = self::themePreference();

        return in_array($preference, ['light', 'dark'], true) ? $preference : null;
    }
}
