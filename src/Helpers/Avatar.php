<?php

namespace ChiefTools\SDK\Helpers;

use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\Entities\User;

final class Avatar
{
    private const PROXY_BASE     = 'https://avatar.assets.chief.app';
    private const PROXY_VERSION  = 1;
    private const PROXY_NAMEHASH = 'namehash';

    public static function of(User $user): self
    {
        return new self($user->name, $user->email, $user->avatar_hash);
    }

    public static function ofTeam(Team $team): self
    {
        return new self($team->name, $team->gravatar_email, $team->avatar_hash);
    }

    public function __construct(
        private readonly string $name = '',
        private readonly ?string $email = null,
        private readonly ?string $avatarHash = null,
    ) {}

    public function url(): string
    {
        if ($this->email === null) {
            return sprintf('%s/%s/%s.jpg', self::PROXY_BASE, self::PROXY_NAMEHASH, $this->nameHash());
        }

        if ($this->avatarHash === null) {
            return sprintf('%s/%d/%s/%s.jpg', self::PROXY_BASE, self::PROXY_VERSION, $this->gravatarHash(), $this->nameHash());
        }

        return sprintf('%s/%d/%s/%s/%s.jpg', self::PROXY_BASE, self::PROXY_VERSION, $this->gravatarHash(), $this->nameHash(), $this->avatarHash);
    }

    private function nameHash(): string
    {
        $initials = '';

        $nameParts = explode(' ', $this->name);

        if (!empty($nameParts[0])) {
            $firstPart = str(array_shift($nameParts));

            $initials = (string)$firstPart->substr(0, 1);

            $lastPart = str(count($nameParts) > 0 ? array_pop($nameParts) : '');

            if ($lastPart->isNotEmpty()) {
                $initials .= $lastPart->substr(0, 1);
            } elseif ($firstPart->length() >= 2) {
                $initials .= $firstPart->substr(1, 1);
            }
        }

        // Make sure we have no more than 2 characters (1 or 2 chars is supported) and they are ASCII
        $initials = str($initials)->substr(0, 2)->ascii()->match('/^[[:alpha:]]*$/');

        // Default to empty if the initials contain non-alpha characters
        if ($initials->isEmpty()) {
            $initials = '';
        }

        // We use a little part of the SHA1 hash of the intials as the name hash
        return substr(sha1($initials), 0, 8);
    }

    private function gravatarHash(): string
    {
        return md5(strtolower(trim($this->email)));
    }
}
