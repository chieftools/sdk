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
        return new self($user->name, $user->email, $user->avatarHash);
    }

    public function __construct(
        private string $name,
        private string $email,
        private ?string $avatarHash = null,
    ) {
    }

    public function url(): string
    {
        if ($this->avatarHash === null) {
            return sprintf('%s/%d/%s/%s.jpg', self::PROXY_BASE, self::PROXY_VERSION, $this->gravatarHash(), $this->nameHash());
        }

        return sprintf('%s/%d/%s/%s/%s.jpg', self::PROXY_BASE, self::PROXY_VERSION, $this->gravatarHash(), $this->nameHash(), $this->avatarHash);
    }

    private function nameHash(): string
    {
        $initials = '';

        $nameParts = explode(' ', $this->name);

        if (!empty($nameParts)) {
            $firstPart = Str::of(array_shift($nameParts));

            $initials = (string)$firstPart->substr(0, 1);

            $lastPart = Str::of(count($nameParts) > 0 ? array_pop($nameParts) : '');

            if ($lastPart->isNotEmpty()) {
                $initials .= $lastPart->substr(0, 1);
            } elseif ($firstPart->length() >= 2) {
                $initials .= $firstPart->substr(1, 1);
            }
        }

        // Make sure we have no more than 2 characters (1 or 2 chars is supported) and they are ASCII
        $initials = Str::of($initials)->substr(0, 2)->ascii();

        // Default to empty if the initials contain non-alpha characters
        if (!$initials->match('/^[[:alpha:]]*$/') || $initials->length() === 0) {
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
