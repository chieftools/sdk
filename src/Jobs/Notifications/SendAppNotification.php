<?php

namespace ChiefTools\SDK\Jobs\Notifications;

use ChiefTools\SDK\Jobs\Job;
use ChiefTools\SDK\API\Client;
use GuzzleHttp\Exception\ClientException;
use ChiefTools\SDK\API\DTO\AppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Sends an app notification through Account Chief; retrying is safe, the idempotency key prevents duplicates.
 */
class SendAppNotification extends Job implements ShouldQueue
{
    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300, 900];

    public function __construct(
        public readonly AppNotification $notification,
    ) {}

    public function handle(Client $mothership): void
    {
        try {
            $mothership->sendAppNotification($this->notification);
        } catch (ClientException $exception) {
            // A refused notification (validation, unknown team) stays refused; only rate limits are worth retrying
            if ($exception->getResponse()->getStatusCode() === 429) {
                throw $exception;
            }

            $this->fail($exception);
        }
    }
}
