<?php

namespace IronGate\Chief\Socialite;

use Laravel\Socialite\Two\User;

class ChiefUser extends User
{
    /**
     * Indicates if the user is an Chief administrator user.
     *
     * @var bool
     */
    public bool $is_admin = false;

    /**
     * Indicates the configured timezone for the user.
     *
     * @var string|null
     */
    public ?string $timezone;

    /**
     * Get the admin status of the user.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Get the timezone for the user.
     *
     * @return string|null
     */
    public function getTimezone(): ?string
    {
        return $this->timezone;
    }
}
