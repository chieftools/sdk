<?php

namespace IronGate\Chief\Helpers;

use Illuminate\Support\Str;
use IronGate\Chief\Entities\User;

final class Avatar
{
    private const PROXY_BASE    = 'https://avatar.assets.chief.app';
    private const PROXY_VERSION = 1;

    public static function of(User $user): self
    {
        return new self($user);
    }

    private function __construct(
        private readonly User $user,
    ) {
    }

    public function url(): string
    {
        $avatarHash = $this->user->avatarHash ?? null;

        if ($avatarHash === null) {
            return sprintf('%s/%d/%s/%s.jpg', self::PROXY_BASE, self::PROXY_VERSION, $this->gravatarHash(), $this->nameHash());
        }

        return sprintf('%s/%d/%s/%s/%s.jpg', self::PROXY_BASE, self::PROXY_VERSION, $this->gravatarHash(), $this->nameHash(), $avatarHash);
    }

    private function nameHash(): string
    {
        $nameParts = explode(' ', $this->user->name);

        $firstPart = array_shift($nameParts)[0];
        $lastPart  = count($nameParts) > 0 ? array_pop($nameParts) : '';

        $initials = $firstPart[0];

        if (!empty($lastPart)) {
            $initials .= $lastPart[0];
        } elseif (strlen($firstPart) >= 2) {
            $initials .= $firstPart[1];
        }

        // Make sure we have no more than 2 characters (1 or 2 chars is supported)
        $initials = Str::of($initials)->substr(0, 2);

        // Default to empty if the initials contain non-alpha characters
        if (!$initials->match('/^[[:alpha:]]*$/') || $initials->length() === 0) {
            $initials = '';
        }

        // We use a little part of the SHA1 hash of the intials as the name hash
        return substr(sha1($initials), 0, 8);
    }

    private function gravatarHash(): string
    {
        return md5(strtolower(trim($this->user->email)));
    }
}
