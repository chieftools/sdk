<?php

namespace IronGate\Chief\Passport;

use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

class MemoizedClientRepository extends ClientRepository
{
    private static array $cache = [];

    public function find($id): ?Client
    {
        if (!isset(self::$cache[$id])) {
            self::$cache[$id] = parent::find($id);
        }

        return self::$cache[$id];
    }

    public function findForUser($clientId, $userId): ?Client
    {
        $client = $this->find($clientId);

        return $client !== null && $client->user_id === $userId
            ? $client
            : null;
    }

    public function update(Client $client, $name, $redirect): Client
    {
        $client = parent::update($client, $name, $redirect);

        self::$cache[$client->id] = $client;

        return $client;
    }

    public function regenerateSecret(Client $client): Client
    {
        $client = parent::regenerateSecret($client);

        self::$cache[$client->id] = $client;

        return $client;
    }

    public function delete(Client $client): void
    {
        unset(self::$cache[$client->id]);

        parent::delete($client);
    }
}
