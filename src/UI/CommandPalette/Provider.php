<?php

namespace ChiefTools\SDK\UI\CommandPalette;

interface Provider
{
    public function key(): string;

    public function label(): string;

    /** @return array<int, string> */
    public function scopes(): array;

    /** @return iterable<\ChiefTools\SDK\UI\CommandPalette\Item> */
    public function search(Query $query): iterable;
}
