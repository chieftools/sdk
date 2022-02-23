<?php

namespace IronGate\Chief\Webhook\Handlers;

use IronGate\Chief\Entities\User;

abstract class BaseHandler implements Handler
{
    protected function getUserById(string $id): ?User
    {
        return config('chief.auth.model')::query()->where('chief_id', '=', $id)->first();
    }

    protected function getUserFromPayload(array $payload): ?User
    {
        $id = array_get($payload, 'data.id');

        if ($id === null) {
            return null;
        }

        return $this->getUserById($id);
    }
}
