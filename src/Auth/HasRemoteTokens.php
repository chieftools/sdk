<?php

namespace ChiefTools\SDK\Auth;

trait HasRemoteTokens
{
    /**
     * The access token the user is using for the current request.
     */
    private ?ChiefRemoteAccessToken $chiefRemoteAccessToken = null;

    public function hasChiefRemoteAccessToken(): bool
    {
        return $this->chiefRemoteAccessToken !== null;
    }

    public function currentChiefRemoteAccessToken(): ChiefRemoteAccessToken
    {
        return $this->chiefRemoteAccessToken;
    }

    public function withChiefRemoteAccessToken(ChiefRemoteAccessToken $accessToken): self
    {
        $this->chiefRemoteAccessToken = $accessToken;

        return $this;
    }
}
