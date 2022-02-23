<?php

namespace IronGate\Chief\API;

use RuntimeException;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\Collection;
use GuzzleHttp\Client as HttpClient;
use Sentry\Tracing\GuzzleTracingMiddleware;

class Client extends HttpClient
{
    /**
     * Get the URL to the Account Chief application.
     */
    public static function getBaseUrl(?string $path = null): string
    {
        $url = rtrim(config('chief.base_url', 'https://account.chief.app'), '/');

        if (!empty($path)) {
            $url .= '/' . ltrim($path, '/');
        }

        return $url;
    }

    public function __construct(array $config = [], array $headers = [], int $timeout = 10)
    {
        $stack = HandlerStack::create();

        if (app()->bound('sentry')) {
            $stack->push(GuzzleTracingMiddleware::trace());
        }

        parent::__construct(array_merge($config, [
            'base_uri'        => self::getBaseUrl(),
            'handler'         => $stack,
            'verify'          => config('services.chief.verify', true),
            'timeout'         => $timeout,
            'connect_timeout' => $timeout,
            'headers'         => array_merge($headers, [
                'User-Agent' => user_agent(),
            ]),
        ]));
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
        $queryParams = [
            'except' => $except,
            'group'  => $group,
        ];

        if ($authenticated !== null) {
            $queryParams['authenticated'] = $authenticated ? '1' : '0';
        }

        $response = $this->get('/api/apps', [
            'query' => array_filter($queryParams),
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Could not retrieve Chief apps from API.');
        }

        return collect(json_decode($response->getBody()->getContents(), true));
    }
}
