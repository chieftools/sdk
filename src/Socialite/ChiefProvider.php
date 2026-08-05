<?php

namespace ChiefTools\SDK\Socialite;

use Illuminate\Support\Str;
use ChiefTools\SDK\API\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Illuminate\Contracts\Encryption\DecryptException;

class ChiefProvider extends AbstractProvider implements ProviderInterface
{
    private const AUTHENTICATION_RETRY_STATE_PREFIX = 'chief-authentication-retry:';

    private bool $authenticationRetry = false;

    protected $scopes = ['profile', 'email', 'teams'];

    protected $usesPKCE = true;

    protected $stateless = false;

    protected $scopeSeparator = ' ';

    public function redirectForAuthenticationRetry(): RedirectResponse
    {
        $this->authenticationRetry = true;

        return $this->redirect();
    }

    public static function hasAuthenticationRetryState(mixed $state): bool
    {
        if (!is_string($state)) {
            return false;
        }

        try {
            $state = Crypt::decryptString($state);
        } catch (DecryptException) {
            return false;
        }

        return Str::startsWith($state, self::AUTHENTICATION_RETRY_STATE_PREFIX);
    }

    protected function getState(): string
    {
        $state = parent::getState();

        if (!$this->authenticationRetry) {
            return $state;
        }

        $this->authenticationRetry = false;

        return Crypt::encryptString(self::AUTHENTICATION_RETRY_STATE_PREFIX . $state);
    }

    protected function getHttpClient()
    {
        if (is_null($this->httpClient)) {
            $this->httpClient = internal_http(options: $this->guzzle);
        }

        return $this->httpClient;
    }

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(Client::getBaseUrl('/login/oauth/authorize'), $state);
    }

    protected function getTokenUrl()
    {
        return Client::getBaseUrl('/api/oauth/token');
    }

    protected function getTokenRevokeUrl()
    {
        return Client::getBaseUrl('/api/oauth/token/revoke');
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

    public function revokeAccessToken($token): void
    {
        $this->getHttpClient()->post($this->getTokenRevokeUrl(), [
            'json'   => [
                'token'         => $token,
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
            ],
            'verify' => config('services.chief.verify', true),
        ]);
    }

    protected function getUserByToken($token)
    {
        $userUrl = Client::getBaseUrl('/api/oauth/userinfo');

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
