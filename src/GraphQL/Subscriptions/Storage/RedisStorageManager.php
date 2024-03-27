<?php

namespace ChiefTools\SDK\GraphQL\Subscriptions\Storage;

use Nuwave\Lighthouse\Subscriptions\Storage\RedisStorageManager as LighthouseRedisStorageManager;

class RedisStorageManager extends LighthouseRedisStorageManager
{
    public function hasSubscribersForTopic(string $topic): bool
    {
        return $this->connection->command('scard', [$this->topicKey($topic)]) > 0;
    }
}
