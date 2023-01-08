<?php

namespace ChiefTools\SDK\GraphQL\Queries;

use ChiefTools\SDK\GraphQL\QueryResolver;

class ApplicationInfo extends QueryResolver
{
    protected function execute(): array
    {
        return [
            'id'               => config('chief.id'),
            'locale'           => app()->getLocale(),
            'version'          => config('app.versionString'),
            'timezone'         => config('app.timezone_user') ?? config('app.timezone'),
            'versionHash'      => config('app.version'),
            'environment'      => app()->environment(),
            'versionFormatted' => config('app.versionString') . ' (' . config('app.version') . ')',
        ];
    }
}
