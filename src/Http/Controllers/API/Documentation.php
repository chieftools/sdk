<?php

namespace ChiefTools\SDK\Http\Controllers\API;

use Illuminate\View\View;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Exceptions\UrlGenerationException;

class Documentation
{
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
        ]);
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

    private function routeUrl(mixed $routeName): ?string
    {
        if (!is_string($routeName) || !Route::has($routeName)) {
            return null;
        }

        try {
            return route($routeName);
        } catch (UrlGenerationException) {
            return null;
        }
    }
}
