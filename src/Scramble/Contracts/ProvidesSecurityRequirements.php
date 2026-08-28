<?php

namespace ChiefTools\SDK\Scramble\Contracts;

interface ProvidesSecurityRequirements
{
    /** @return list<array<string, list<string>>> */
    public static function securityRequirements(): array;
}
