<?php

namespace ChiefTools\SDK\Http\Controllers\API;

use Illuminate\View\View;
use Illuminate\Support\Facades\Route;
use ChiefTools\SDK\Webhook\Outbound\WebhookConfig;
use Dedoc\Scramble\Attributes\ExcludeRouteFromDocs;
use Illuminate\Routing\Exceptions\UrlGenerationException;

class Documentation
{
    /** The page that links the API documentation is no API endpoint itself. */
    #[ExcludeRouteFromDocs]
    public function __invoke(): View
    {
        return view('chief::api.docs.index', [
            'authenticationDocumentationUrl' => chief_developer_docs_url('authentication'),
            'developerDocumentationUrl'      => chief_developer_docs_url(),
            'personalTokensUrl'              => chief_base_url('api/tokens'),
            'protocols'                      => collect([
                $this->protocol('rest'),
                $this->protocol('graphql'),
            ])->filter()->values(),
            'teamTokensUrl'                  => chief_base_url('team/select'),
            'mcp'                            => chief_mcp_endpoint() === null ? null : [
                'endpoint'          => chief_mcp_endpoint(),
                'documentation_url' => chief_mcp_documentation_url(),
            ],
            'webhooks'                       => $this->webhooks(),
        ]);
    }

    /**
     * The app's webhooks guide, `/developers/{app}/webhooks`, and its page to manage endpoints for the team the user last
     * worked in, or a choice of team when there is none. Null when the app sends no webhooks.
     *
     * @return array{documentation_url: string, manage_url: ?string}|null
     */
    private function webhooks(): ?array
    {
        if (!WebhookConfig::enabled()) {
            return null;
        }

        return [
            'documentation_url' => chief_developer_docs_url(trim(config('chief.id') . '/webhooks', '/')),
            'manage_url'        => Route::has('webhooks')
                ? $this->routeUrl('webhooks', ['team_hint' => url()->getDefaultParameters()['team_hint'] ?? 'select'])
                : null,
        ];
    }

    /**
     * @param 'rest'|'graphql' $protocol
     *
     * @return array{label: string, icon: string, description: string, documentation_url: ?string, playground_url: ?string}|null
     */
    private function protocol(string $protocol): ?array
    {
        $configuration = config("chief.api_documentation.{$protocol}");

        if (!is_array($configuration)) {
            return null;
        }

        $documentationUrl = chief_http_url($configuration['documentation_url'] ?? null)
            ?? $this->routeUrl($configuration['documentation_route'] ?? null);
        $playgroundUrl    = $this->routeUrl($configuration['playground_route'] ?? null);

        if ($documentationUrl === null && $playgroundUrl === null) {
            return null;
        }

        $details = $protocol === 'rest'
            ? [
                'label'       => 'REST API',
                'icon'        => 'fa-code',
                'description' => 'Browse endpoints, request parameters, response schemas, and required scopes.',
            ]
            : [
                'label'       => 'GraphQL API',
                'icon'        => 'fa-hexagon-nodes',
                'description' => 'Explore the schema, build queries, and inspect the available fields and operations.',
            ];

        return [
            'label'             => $details['label'],
            'icon'              => $details['icon'],
            'description'       => $details['description'],
            'documentation_url' => $documentationUrl,
            'playground_url'    => $playgroundUrl,
        ];
    }

    /** @param array<string, string> $parameters */
    private function routeUrl(mixed $routeName, array $parameters = []): ?string
    {
        if (!is_string($routeName) || !Route::has($routeName)) {
            return null;
        }

        try {
            return route($routeName, $parameters);
        } catch (UrlGenerationException) {
            return null;
        }
    }
}
