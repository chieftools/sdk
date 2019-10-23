<?php

namespace IronGate\Integration\API;

use RuntimeException;
use Illuminate\Support\Collection;
use GuzzleHttp\Client as HttpClient;

class Client extends HttpClient
{
    /**
     * Get the base URL for the account chief.
     *
     * @return string
     */
    public static function getBaseUrl(): string
    {
        return config('chief.base_url', 'https://account.chief.app');
    }

    /**
     * {@inheritdoc}
     */
    public function __construct(array $config = [])
    {
        parent::__construct(array_merge([
            'base_uri' => self::getBaseUrl(),
            'verify'   => config('services.chief.verify', true),
        ], $config));
    }

    /**
     * Get the information for an app by it's ID.
     *
     * @param string $id
     *
     * @return array
     */
    public function app(string $id): array
    {
        if (empty($id)) {
            throw new RuntimeException('The app ID cannot be empty!');
        }

        $response = $this->get("/api/app/{$id}");

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Could not retrieve Chief app from API.');
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get all apps available from the API.
     *
     * @param string|null $except        Comma seperated string of apps to exclude
     * @param string|null $group         The group of apps to retrieve (primary/secondary/...)
     * @param bool|null   $authenticated Indicate if only authenticated (or un-authenticated) apps should be retrieved
     *
     * @return \Illuminate\Support\Collection
     */
    public function apps(?string $except = null, ?string $group = null, ?bool $authenticated = null): Collection
    {
        $response = $this->get('/api/apps', [
            'query' => array_filter([
                'except'        => $except,
                'group'         => $group,
                'authenticated' => $authenticated === null ? null : ($authenticated ? '1' : '0'),
            ]),
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Could not retrieve Chief apps from API.');
        }

        return collect(json_decode($response->getBody()->getContents(), true));
    }
}
