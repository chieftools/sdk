<?php

namespace ChiefTools\SDK\Scramble;

use Illuminate\Support\Str;
use Dedoc\Scramble\Support\RouteInfo;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Contracts\OperationTransformer;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use ChiefTools\SDK\Scramble\Contracts\ProvidesSecurityRequirements;

final class ScopeSecurityTransformer implements OperationTransformer
{
    private const array SECURITY_SCHEMES = ['openid', 'ctp', 'ctt'];

    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        $middleware = collect($routeInfo->route->gatherMiddleware())
            ->filter(static fn (mixed $middleware): bool => is_string($middleware));

        $providedRequirements = $middleware
            ->map(static fn (string $middleware): string => Str::before($middleware, ':'))
            ->filter(static fn (string $middleware): bool => is_a($middleware, ProvidesSecurityRequirements::class, true))
            ->flatMap(static fn (string $middleware): array => $middleware::securityRequirements())
            ->values()
            ->all();

        if ($providedRequirements !== []) {
            $operation->security = array_map(
                static fn (array $requirement): SecurityRequirement => new SecurityRequirement($requirement),
                $providedRequirements,
            );

            return;
        }

        $scopeGroups = $middleware
            ->filter(static fn (string $middleware): bool => str_starts_with($middleware, 'scope:'))
            ->map(static fn (string $middleware): array => collect(explode(',', Str::after($middleware, 'scope:')))
                ->map(static fn (string $scope): string => trim($scope))
                ->filter()
                ->unique()
                ->values()
                ->all())
            ->filter()
            ->values()
            ->all();

        if ($scopeGroups === []) {
            return;
        }

        $scopeAlternatives = $this->scopeAlternatives($scopeGroups);

        $operation->security = collect(self::SECURITY_SCHEMES)
            ->flatMap(static fn (string $scheme): array => array_map(
                static fn (array $scopes): SecurityRequirement => new SecurityRequirement([$scheme => $scopes]),
                $scopeAlternatives,
            ))
            ->all();
    }

    /**
     * @param list<list<string>> $scopeGroups
     *
     * @return list<list<string>>
     */
    private function scopeAlternatives(array $scopeGroups): array
    {
        $alternatives = [[]];

        foreach ($scopeGroups as $scopeGroup) {
            $nextAlternatives = [];

            foreach ($alternatives as $alternative) {
                foreach ($scopeGroup as $scope) {
                    $combinedScopes = array_values(array_unique([...$alternative, $scope]));

                    $nextAlternatives[implode("\0", $combinedScopes)] = $combinedScopes;
                }
            }

            $alternatives = array_values($nextAlternatives);
        }

        return $alternatives;
    }
}
