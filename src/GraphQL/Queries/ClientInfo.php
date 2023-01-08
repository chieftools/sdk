<?php

namespace ChiefTools\SDK\GraphQL\Queries;

use ChiefTools\SDK\GraphQL\QueryResolver;

class ClientInfo extends QueryResolver
{
    protected function execute(): array
    {
        $name = $version = null;

        $clientHeader = $this->request()->header('graphql-client');

        if (!empty($clientHeader)) {
            [$name, $version] = explode(':', $clientHeader . ':');
        }

        return compact('name', 'version');
    }
}
