<?php

namespace ChiefTools\SDK\API\DTO;

use InvalidArgumentException;
use Illuminate\Contracts\Support\Arrayable;

/**
 * A notification from the app to its users through the Chief Tools app. The banner shows "Chief Tools" with the app
 * and team under the title; tapping it opens the path inside the app.
 *
 * Only send it to users whose `wantsAppNotifications()` is true; Account Chief checks every user again.
 */
class AppNotification implements Arrayable
{
    /**
     * @param list<string> $users          the Chief IDs of the users, at most 100
     * @param string       $type           the app's own event type, e.g. `domain.renewed`
     * @param string       $idempotencyKey keeps a retried request from sending the notification twice
     * @param string|null  $team           the slug of the team the notification is about
     * @param string|null  $path           where tapping the notification leads inside the app, starting with a slash
     * @param string|null  $collapseKey    a newer notification with the same key replaces the older one
     */
    public function __construct(
        public readonly array $users,
        public readonly string $type,
        public readonly string $title,
        public readonly string $body,
        public readonly string $idempotencyKey,
        public readonly ?string $team = null,
        public readonly ?string $path = null,
        public readonly ?string $collapseKey = null,
        public readonly bool $timeSensitive = false,
        public readonly ?int $ttlSeconds = null,
    ) {
        if ($users === [] || count($users) > 100) {
            throw new InvalidArgumentException('An app notification goes to 1 to 100 users.');
        }
    }

    public function toArray(): array
    {
        return array_filter([
            'users'           => array_values($this->users),
            'team'            => $this->team,
            'type'            => $this->type,
            'title'           => $this->title,
            'body'            => $this->body,
            'path'            => $this->path,
            'idempotency_key' => $this->idempotencyKey,
            'collapse_key'    => $this->collapseKey,
            'time_sensitive'  => $this->timeSensitive,
            'ttl'             => $this->ttlSeconds,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
