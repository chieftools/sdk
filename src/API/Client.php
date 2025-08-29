<?php

namespace ChiefTools\SDK\API;

use Exception;
use Carbon\Carbon;
use RuntimeException;
use ChiefTools\SDK\Entities\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use GuzzleHttp\Client as HttpClient;
use ChiefTools\SDK\Socialite\ChiefTeam;
use ChiefTools\SDK\Socialite\ChiefUser;
use GuzzleHttp\Exception\GuzzleException;
use ChiefTools\SDK\Jobs\Reporting\ReportUsage;
use ChiefTools\SDK\Auth\ChiefRemoteAccessToken;

class Client
{
    private HttpClient $http;

    public function __construct(?HttpClient $http = null)
    {
        $this->http = $http ?? internal_http(self::getBaseUrl(), [
            'Accept' => 'application/json',
        ]);
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

        $response = $this->http->get("/api/app/{$id}");

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Could not retrieve Chief app from API.');
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get the pricing HTML for an app by it's ID.
     */
    public function appPricing(string $id, bool $withoutFeatured = false): HtmlString
    {
        if (empty($id)) {
            throw new RuntimeException('The app ID cannot be empty!');
        }

        $response = $this->http->get("/api/app/{$id}/pricing", [
            'query' => [
                'without_featured' => $withoutFeatured ? '1' : '0',
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Could not retrieve app pricing from API.');
        }

        return new HtmlString($response->getBody()->getContents());
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

        $response = $this->http->get('/api/apps', [
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
     * @param array  $extra
     *
     * @return \ChiefTools\SDK\Socialite\ChiefUser|null
     */
    public function user(string $uuid, array $extra = []): ?ChiefUser
    {
        try {
            $response = $this->http->get("/api/user/{$uuid}", [
                'query'   => $extra,
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
     * Retrieve user info from the mothership.
     *
     * @param string $email
     * @param array  $extra
     *
     * @return \ChiefTools\SDK\Socialite\ChiefUser|null
     */
    public function userByEmail(string $email, array $extra = []): ?ChiefUser
    {
        try {
            $response = $this->http->get('/api/user/find-by-email', [
                'query'   => array_merge($extra, [
                    'email' => $email,
                ]),
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
            $response = $this->http->get("/api/team/{$slug}", [
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
     * Generate an access token for a user.
     *
     * The token is scoped to the requesting app and the user's team.
     *
     * @param string $uuid
     *
     * @return \ChiefTools\SDK\Auth\ChiefRemoteAccessToken|null
     */
    public function generateAccessToken(string $uuid): ?ChiefRemoteAccessToken
    {
        try {
            $response = $this->http->post("/api/user/{$uuid}/access_token", [
                'headers' => $this->internalAuthHeaders(),
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = json_decode($response->getBody()->getContents(), true);

            return new ChiefRemoteAccessToken(
                scopes: $data['scopes'],
                userId: $data['user_id'],
                teamId: $data['team_id'],
                expiresAt: $data['expires_at'] ? Carbon::createFromTimestamp($data['expires_at']) : null,
                plainTextToken: $data['access_token'],
            );
        } catch (GuzzleException) {
            return null;
        }
    }

    /**
     * Validate a access token with the mothership.
     *
     * @param string $token
     *
     * @return array{scopes: array, user_id: string|null, team_id: int|null, expires_at: ?int}|null
     */
    public function validateAccessToken(string $token): ?array
    {
        if (empty($token)) {
            throw new RuntimeException('The token cannot be empty!');
        }

        $response = $this->http->get('/api/auth/validate-token', [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                ...$this->internalAuthHeaders(),
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Could not validate token.');
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
        $response = $this->http->put("/api/team/{$teamSlug}/billing/plan/usage/{$usageId}", [
            'json'    => compact('usage'),
            'headers' => $this->internalAuthHeaders(),
        ]);

        if ($response->getStatusCode() !== 204) {
            throw new RuntimeException('Could not report usage.');
        }
    }

    /**
     * Report plan usage back to the mothership in bulk.
     *
     * @param array<int, \ChiefTools\SDK\API\DTO\TeamUsageReport> $reports
     *
     * @return void
     */
    public function reportUsageInBulk(array $reports): void
    {
        if (count($reports) > 100) {
            foreach (array_chunk($reports, 100) as $chunk) {
                $this->reportUsageInBulk($chunk);
            }

            return;
        }

        $reports = collect($reports)->toArray();

        $response = $this->http->put('/api/bulk/team/billing/plan/usage', [
            'json'    => compact('reports'),
            'headers' => $this->internalAuthHeaders(),
        ]);

        if ($response->getStatusCode() !== 204) {
            throw new RuntimeException('Could not report bulk usages.');
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
        $response = $this->http->post("/api/team/{$teamSlug}/billing/plan/beta/activate", [
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
            $this->http->post("/api/team/{$team->slug}/activity", [
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
}
