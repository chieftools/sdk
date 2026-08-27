<?php

namespace ChiefTools\SDK\UI\Shell;

use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\Entities\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Generic command-palette shell command builders shared across all
 * team-enabled Chief Tools apps. Each method returns a Collection of
 * static command entries that the modern shell renders in the palette.
 *
 * Tools compose these alongside their own tool-specific commands in
 * resources/views/partial/menu.blade.php, e.g.:
 *
 *     $shellCommands = collect()
 *         ->merge(Shell\Commands::account())
 *         ->merge(Shell\Commands::developer())
 *         ->when($team, fn ($c) => $c
 *             ->merge($toolSpecificCommands)
 *             ->merge(Shell\Commands::teamManagement($team))
 *             ->merge(Shell\Commands::teamSwitcher($user, $team)));
 *
 * Each individual block is a no-op (returns an empty collection) when its
 * underlying routes are not registered, so callers can compose them
 * unconditionally. App-specific dashboards (Admin, Horizon, …) are
 * intentionally *not* provided here — not every tool exposes those routes
 * on its app domain, so each tool defines its own admin block inline.
 */
class Commands
{
    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    public static function account(): Collection
    {
        $commands = collect();

        if (Route::has('account.profile')) {
            $commands->push([
                'label'       => 'Profile',
                'href'        => route('account.profile'),
                'icon'        => 'fad fa-user-circle',
                'description' => 'Account',
            ]);
        }

        if (Route::has('account.preferences')) {
            $commands->push([
                'label'       => 'Preferences',
                'href'        => route('account.preferences'),
                'icon'        => 'fad fa-cog',
                'description' => 'Account',
            ]);
        }

        return $commands;
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    public static function developer(): Collection
    {
        $commands = collect();

        if (Route::has('api.docs')) {
            $commands->push([
                'label'       => 'API documentation',
                'href'        => route('api.docs'),
                'icon'        => 'fad fa-rectangle-api',
                'description' => 'Developer',
            ]);
        }

        return $commands;
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    public static function teamManagement(Team $team): Collection
    {
        $commands = collect();

        if (Route::has('team.chief.manage.plan')) {
            $commands->push([
                'label'       => 'Manage plan',
                'href'        => route('team.chief.manage.plan', [$team]),
                'icon'        => 'fad fa-credit-card',
                'description' => 'Team',
                'target'      => '_blank',
            ]);
        }

        if (Route::has('team.chief.manage.single')) {
            $commands->push([
                'label'       => 'Manage team',
                'href'        => route('team.chief.manage.single', [$team]),
                'icon'        => 'fad fa-gear',
                'description' => 'Team',
                'target'      => '_blank',
            ]);
        }

        if (Route::has('team.chief.manage')) {
            $commands->push([
                'label'       => 'Manage teams',
                'href'        => route('team.chief.manage', [$team]),
                'icon'        => 'fad fa-people-group',
                'description' => 'Teams',
                'target'      => '_blank',
            ]);
        }

        return $commands;
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    public static function teamSwitcher(User $user, Team $currentTeam): Collection
    {
        if (!Route::has('team.switch')) {
            return collect();
        }

        $appTitle = (string)config('app.title', config('app.name', 'Chief Tools'));

        /** @var \Illuminate\Support\Collection<int, array<string, mixed>> $commands */
        $commands = $user->teams
            ->reject(static fn (Team $team): bool => $team->is($currentTeam))
            ->map(static fn (Team $team): array => [
                'label'       => "Switch to {$team} team",
                'href'        => route('team.switch', [$team]),
                'icon'        => 'fad fa-arrow-right-arrow-left',
                'category'    => "{$appTitle} > Teams",
                'description' => (string)$team,
            ])
            ->values();

        return $commands;
    }
}
