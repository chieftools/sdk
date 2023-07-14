<?php

namespace ChiefTools\SDK\Socialite;

use ChiefTools\SDK\API\Client;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;

class ChiefProvider extends AbstractProvider implements ProviderInterface
{
    protected function getHttpClient()
    {
        if (is_null($this->httpClient)) {
            $this->httpClient = http(options: $this->guzzle);
        }

        return $this->httpClient;
    }

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(Client::getBaseUrl('/oauth/authorize'), $state);
    }

    protected function getTokenUrl()
    {
        return Client::getBaseUrl('/oauth/token');
    }

    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'json'    => $this->getTokenFields($code),
            'verify'  => config('services.chief.verify', true),
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    protected function getUserByToken($token)
    {
        $userUrl = Client::getBaseUrl('/api/me');

        $response = $this->getHttpClient()->get($userUrl, [
            'verify'  => config('services.chief.verify', true),
            'headers' => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    protected function mapUserToObject(array $user): ChiefUser
    {
        return new ChiefUser($user);
    }
}
