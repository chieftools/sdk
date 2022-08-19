<?php

namespace ChiefTools\SDK\Socialite;

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
     * The avatar hash for this user.
     */
    public readonly ?string $avatar_hash;

    /**
     * The teams the user is a member of.
     *
     * @var array<int, \ChiefTools\SDK\Socialite\ChiefTeam>
     */
    public readonly array $teams;

    public function __construct(array $user)
    {
        $this->setRaw($user)->map([
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
        ]);

        $this->teams = array_map(
            static fn (array $team) => ChiefTeam::fromArray($team),
            $user['teams'] ?? [],
        );

        $this->timezone        = $user['timezone'];
        $this->is_admin        = (bool)($user['is_admin'] ?? false);
        $this->avatar_hash     = $user['avatar_hash'];
        $this->default_team_id = empty($user['default_team_id']) ? null : (int)$user['default_team_id'];
    }
}
