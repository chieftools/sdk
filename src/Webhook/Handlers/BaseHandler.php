<?php

namespace ChiefTools\SDK\Webhook\Handlers;

use ChiefTools\SDK\Chief;
use ChiefTools\SDK\Entities\Team;
use ChiefTools\SDK\Entities\User;

abstract class BaseHandler implements Handler
{
    protected function getUserById(string $id): ?User
    {
        return Chief::userModel()::query()->where('chief_id', '=', $id)->first();
    }

    protected function getTeamById(int $id): ?Team
    {
        return Chief::teamModel()::query()->find($id);
    }

    protected function getUserFromPayload(array $payload): ?User
    {
        $id = array_get($payload, 'data.id');

        if ($id === null) {
            return null;
        }

        return $this->getUserById($id);
    }

    protected function getTeamFromPayload(array $payload): ?Team
    {
        $id = array_get($payload, 'data.id');

        if ($id === null) {
            return null;
        }

        return $this->getTeamById($id);
    }
}
