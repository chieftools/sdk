<?php

namespace ChiefTools\SDK\UI\CommandPalette;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Container\Container;

readonly class Manager
{
    public function __construct(
        private Container $container,
    ) {}

    /** @return \Illuminate\Support\Collection<int, \ChiefTools\SDK\UI\CommandPalette\Item> */
    public function search(Query $query): Collection
    {
        if (!$query->hasSearchableTerm()) {
            return collect();
        }

        return $this->scopedProviders($this->providers(), $query)
            ->flatMap(static fn (Provider $provider): iterable => $provider->search($query))
            ->sortBy([
                ['score', 'desc'],
                ['title', 'asc'],
            ])
            ->take($query->limit)
            ->values();
    }

    /** @return \Illuminate\Support\Collection<int, \ChiefTools\SDK\UI\CommandPalette\Provider> */
    private function providers(): Collection
    {
        return collect(config('chief.shell.command_palette_providers', []))
            ->map(fn (string|Provider $provider): mixed => is_string($provider) ? $this->container->make($provider) : $provider)
            ->filter(static fn (mixed $provider): bool => $provider instanceof Provider)
            ->values();
    }

    /**
     * @param \Illuminate\Support\Collection<int, \ChiefTools\SDK\UI\CommandPalette\Provider> $providers
     *
     * @return \Illuminate\Support\Collection<int, \ChiefTools\SDK\UI\CommandPalette\Provider>
     */
    private function scopedProviders(Collection $providers, Query $query): Collection
    {
        if (!$query->scoped) {
            return $providers;
        }

        $scopeParts = $query->normalizedScopeParts();

        if ($scopeParts === []) {
            return $providers;
        }

        if (count($scopeParts) === 1) {
            $resourceMatches = $providers
                ->filter(fn (Provider $provider): bool => $this->matchesResourceScope($provider, $scopeParts[0]))
                ->values();

            if ($resourceMatches->isNotEmpty()) {
                return $resourceMatches;
            }
        }

        return $providers
            ->filter(fn (Provider $provider): bool => $this->matchesPathScope($provider, $scopeParts))
            ->values();
    }

    private function matchesResourceScope(Provider $provider, string $scope): bool
    {
        return $this->resourceScopeCandidates($provider)
            ->contains(fn (string $candidate): bool => $this->segmentMatches($candidate, $scope));
    }

    private function matchesPathScope(Provider $provider, array $scopeParts): bool
    {
        return $this->pathScopeCandidates($provider)
            ->contains(fn (array $candidateParts): bool => $this->pathMatches($candidateParts, $scopeParts));
    }

    /** @return \Illuminate\Support\Collection<int, non-empty-string> */
    private function resourceScopeCandidates(Provider $provider): Collection
    {
        return collect([
            $provider->label(),
            Str::afterLast($provider->key(), '.'),
            ...collect($provider->scopes())
                ->map(fn (string $scope): string => $this->lastScopePart($scope))
                ->filter(fn (string $scope): bool => $this->normalize($scope) === $this->normalize($provider->label()))
                ->all(),
            ...collect($provider->scopes())
                ->filter(static fn (string $scope): bool => str_contains($scope, '>'))
                ->map(fn (string $scope): string => $this->lastScopePart($scope))
                ->all(),
        ])
            ->map(fn (string $candidate): string => $this->normalize($candidate))
            ->reject(static fn (string $candidate): bool => $candidate === '')
            ->unique()
            ->values();
    }

    /** @return \Illuminate\Support\Collection<int, non-empty-array<int, string>> */
    private function pathScopeCandidates(Provider $provider): Collection
    {
        return collect([
            $provider->label(),
            ...$provider->scopes(),
        ])
            ->map(fn (string $scope): array => $this->scopeParts($scope))
            ->reject(static fn (array $scopeParts): bool => $scopeParts === [])
            ->values();
    }

    private function pathMatches(array $candidateParts, array $scopeParts): bool
    {
        if (count($candidateParts) < count($scopeParts)) {
            return false;
        }

        foreach ($scopeParts as $index => $scopePart) {
            if (!$this->segmentMatches($candidateParts[$index] ?? '', $scopePart)) {
                return false;
            }
        }

        return true;
    }

    /** @return array<int, string> */
    private function scopeParts(string $scope): array
    {
        return collect(explode('>', $scope))
            ->map(fn (string $part): string => $this->normalize($part))
            ->filter()
            ->values()
            ->all();
    }

    private function lastScopePart(string $scope): string
    {
        return trim((string)collect(explode('>', $scope))->last());
    }

    private function segmentMatches(string $candidate, string $scope): bool
    {
        $candidate = $this->normalize($candidate);

        return $candidate === $scope
               || str_starts_with($candidate, $scope)
               || (mb_strlen($scope) >= 3 && str_contains($candidate, $scope))
               || (mb_strlen($scope) >= 3 && $this->fuzzyMatches($candidate, $scope));
    }

    private function fuzzyMatches(string $candidate, string $scope): bool
    {
        $position = 0;

        foreach (mb_str_split($scope) as $character) {
            $position = mb_strpos($candidate, $character, $position);

            if ($position === false) {
                return false;
            }

            $position++;
        }

        return true;
    }

    private function normalize(string $value): string
    {
        return Str::of($value)
            ->replace(['.', '_', '-'], ' ')
            ->lower()
            ->squish()
            ->toString();
    }
}
