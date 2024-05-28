<?php

namespace ChiefTools\SDK\Auth;

interface AuthenticatesWithRemoteToken
{
    public function hasChiefRemoteAccessToken();

    public function getChiefRemoteAccessToken(): ?ChiefRemoteAccessToken;

    public function withChiefRemoteAccessToken(ChiefRemoteAccessToken $accessToken): static;
}
