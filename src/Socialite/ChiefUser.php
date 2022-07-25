<?php

namespace IronGate\Chief\Socialite;

use Laravel\Socialite\Two\User;

class ChiefUser extends User
{
    /**
     * Indicates if the user is an Chief administrator user.
     */
    public readonly bool $is_admin;

    /**
     * Indicates the configured timezone for the user.
     */
    public readonly ?string $timezone;

    /**
     * The ID of the default team for this user.
     */
    public readonly ?int $default_team_id;

    /**
     * The teams the user is a member of.
     *
     * @var array<int, \IronGate\Chief\Socialite\ChiefTeam>
     */
    public readonly array $teams;

    public function __construct(array $user)
    {
        $this->setRaw($user)->map([
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
        ]);

        $this->timezone        = $user['timezone'];
        $this->is_admin        = (bool)($user['is_admin'] ?? false);
        $this->default_team_id = empty($user['default_team_id']) ? null : (int)$user['default_team_id'];

        $this->teams = array_map(static fn (array $team) => new ChiefTeam(
            id: $team['id'],
            slug: $team['slug'],
            name: $team['name'],
            limits: $team['limits'],
        ), $user['teams'] ?? []);
    }
}
