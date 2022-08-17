<?php

namespace ChiefTools\SDK\API;

use Exception;
use RuntimeException;
use GuzzleHttp\HandlerStack;
use ChiefTools\SDK\Entities\Team;
use Illuminate\Support\Collection;
use GuzzleHttp\Client as HttpClient;
use ChiefTools\SDK\Socialite\ChiefTeam;
use ChiefTools\SDK\Socialite\ChiefUser;
use GuzzleHttp\Exception\GuzzleException;
use Sentry\Tracing\GuzzleTracingMiddleware;
use ChiefTools\SDK\Jobs\Reporting\ReportUsage;

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
                'Accept'     => 'application/json',
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

    /**
     * Retrieve user info from the mothership.
     *
     * @param string $uuid
     *
     * @return \ChiefTools\SDK\Socialite\ChiefUser|null
     */
    public function user(string $uuid): ?ChiefUser
    {
        try {
            $response = $this->get("/api/user/{$uuid}", [
                'headers' => $this->internalAuthHeaders(),
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = json_decode($response->getBody()->getContents(), true);

            return new ChiefUser($data);
        } catch (GuzzleException) {
            return null;
        }
    }

    /**
     * Retrieve team info from the mothership.
     *
     * @param string $slug
     *
     * @return \ChiefTools\SDK\Socialite\ChiefTeam|null
     */
    public function team(string $slug): ?ChiefTeam
    {
        try {
            $response = $this->get("/api/team/{$slug}", [
                'headers' => $this->internalAuthHeaders(),
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = json_decode($response->getBody()->getContents(), true);

            return ChiefTeam::fromArray($data);
        } catch (GuzzleException) {
            return null;
        }
    }

    /**
     * Validate a PAT with the mothership.
     *
     * @param string $pat
     *
     * @return array{user_id: string, token_id: string, expires_at: ?int}|null
     */
    public function validatePAT(string $pat): ?array
    {
        if (empty($pat)) {
            throw new RuntimeException('The PAT cannot be empty!');
        }

        $response = $this->get('/api/auth/validate-pat', [
            'headers' => [
                'Authorization' => "Bearer {$pat}",
                ...$this->internalAuthHeaders(),
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Could not validate PAT.');
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Report plan usage back to the mothership.
     *
     * @param string $teamSlug
     * @param string $usageId
     * @param int    $usage
     *
     * @return void
     */
    public function reportUsage(string $teamSlug, string $usageId, int $usage): void
    {
        $response = $this->put("/api/team/{$teamSlug}/billing/plan/usage/{$usageId}", [
            'json'    => compact('usage'),
            'headers' => $this->internalAuthHeaders(),
        ]);

        if ($response->getStatusCode() !== 204) {
            throw new RuntimeException('Could not report usage.');
        }
    }

    /**
     * Report plan usage back to the mothership through a async job.
     *
     * @param string $teamSlug
     * @param string $usageId
     * @param int    $usage
     *
     * @return void
     */
    public static function reportUsageAsync(string $teamSlug, string $usageId, int $usage): void
    {
        dispatch(new ReportUsage($teamSlug, $usageId, $usage));
    }

    /**
     * Instruct the mothership to activate the `beta` plan for the team.
     *
     * @param string $teamSlug
     *
     * @return void
     */
    public function activateBetaPlan(string $teamSlug): void
    {
        $response = $this->post("/api/team/{$teamSlug}/billing/plan/beta/activate", [
            'headers' => $this->internalAuthHeaders(),
        ]);

        if ($response->getStatusCode() !== 204) {
            throw new RuntimeException('Could not activate beta plan for team.');
        }
    }

    /**
     * Report that there was activity on the team back to the mothership.
     *
     * @param \ChiefTools\SDK\Entities\Team $team
     *
     * @return void
     */
    public function reportActivity(Team $team): void
    {
        try {
            $this->post("/api/team/{$team->slug}/activity", [
                'headers' => $this->internalAuthHeaders(),
            ]);
        } catch (Exception) {
            // We don't really care if this fails, there will be next attempts
        }
    }

    /**
     * Authentication headers needed to talk about private things with the mothership.
     *
     * @return array
     */
    private function internalAuthHeaders(): array
    {
        return [
            'X-Chief-App'    => config('chief.id'),
            'X-Chief-Secret' => config('chief.secret'),
        ];
    }
}
