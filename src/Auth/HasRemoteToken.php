<?php

namespace ChiefTools\SDK\Auth;

/**
 * @phpstan-require-implements \Illuminate\Contracts\Auth\Authenticatable
 * @phpstan-require-implements \ChiefTools\SDK\Auth\AuthenticatesWithRemoteToken
 */
trait HasRemoteToken
{
    /**
     * The access token the user is using for the current request.
     */
    private ?ChiefRemoteAccessToken $chiefRemoteAccessToken = null;

    public function hasChiefRemoteAccessToken(): bool
    {
        return $this->chiefRemoteAccessToken !== null;
    }

    public function getChiefRemoteAccessToken(): ?ChiefRemoteAccessToken
    {
        return $this->chiefRemoteAccessToken;
    }

    public function withChiefRemoteAccessToken(ChiefRemoteAccessToken $accessToken): static
    {
        $this->chiefRemoteAccessToken = $accessToken;

        return $this;
    }
}
