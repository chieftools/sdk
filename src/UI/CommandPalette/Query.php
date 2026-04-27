<?php

namespace ChiefTools\SDK\UI\CommandPalette;

use Illuminate\Support\Str;

readonly class Query
{
    public string $scope;

    /** @var array<int, string> */
    public array $scopeParts;

    public string $term;

    public bool $scoped;

    public function __construct(
        public string $query,
        public int $limit = 8,
    ) {
        $parts = collect(explode('>', $query))
            ->map(static fn (string $part): string => trim($part));

        $this->scoped = $parts->count() > 1;

        if ($this->scoped) {
            $this->term       = (string)$parts->pop();
            $this->scopeParts = $parts
                ->filter(static fn (string $part): bool => $part !== '')
                ->values()
                ->all();
            $this->scope = implode(' > ', $this->scopeParts);

            return;
        }

        $this->scope      = '';
        $this->scopeParts = [];
        $this->term       = trim($query);
    }

    public function hasSearchableTerm(): bool
    {
        return mb_strlen($this->normalizedTerm()) >= ($this->scoped ? 1 : 2);
    }

    public function normalizedScope(): string
    {
        return Str::of($this->scope)->lower()->squish()->toString();
    }

    /** @return array<int, string> */
    public function normalizedScopeParts(): array
    {
        return collect($this->scopeParts)
            ->map(static fn (string $part): string => Str::of($part)->lower()->squish()->toString())
            ->filter(static fn (string $part): bool => $part !== '')
            ->values()
            ->all();
    }

    public function normalizedTerm(): string
    {
        return Str::of($this->term)->lower()->squish()->toString();
    }
}
