<?php

namespace ChiefTools\SDK\Webhook\Outbound\Jobs;

use Throwable;
use ChiefTools\SDK\Jobs\Job;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Queue\ShouldQueue;
use ChiefTools\SDK\Webhook\Outbound\Webhooks;
use ChiefTools\SDK\Webhook\Outbound\Deliverer;
use ChiefTools\SDK\Webhook\Outbound\WebhookConfig;
use ChiefTools\SDK\Webhook\Outbound\EndpointPacing;
use ChiefTools\SDK\Webhook\Outbound\Models\Delivery;

/**
 * Makes one attempt of a delivery. Retries are not job retries: a failed attempt schedules the next one on the
 * delivery and `chief:webhooks:dispatch-due` queues it when it is due.
 *
 * When its endpoint has no room, see `\ChiefTools\SDK\Webhook\Outbound\EndpointPacing`, the job comes back a little
 * later without using an attempt. An attempt that keeps waiting is handed to the sweeper instead, so a backlog does not
 * keep the queue busy with jobs that only wait, and a queue that ignores delays cannot come back forever.
 */
class SendDelivery extends Job implements ShouldQueue
{
    /** How often an attempt waits for its endpoint before the sweeper takes it over. */
    public const int MAX_WAITS = 20;

    private const int MAX_WAIT_SECONDS = 30;

    public int $tries = 1;

    public int $timeout = 60;

    /**
     * @param int  $attempts the attempts the delivery had when this was queued, a stale copy of the job does nothing
     * @param bool $manual   someone resent it, which works whatever the state of the delivery
     * @param int  $waits    how often it already waited for room at its endpoint
     */
    public function __construct(
        public readonly string $deliveryId,
        public readonly int $attempts,
        public readonly bool $manual = false,
        public readonly int $waits = 0,
    ) {
        $this->onQueue(WebhookConfig::queue());
    }

    public function handle(Deliverer $deliverer, EndpointPacing $pacing): void
    {
        $lock = Cache::lock("chief-webhooks:delivery:{$this->deliveryId}", $this->timeout);

        if (!$lock->get()) {
            return;
        }

        try {
            $delivery = Delivery::query()->find($this->deliveryId);

            if ($delivery === null || $delivery->attempts !== $this->attempts || (!$this->manual && !$delivery->isPending())) {
                return;
            }

            $wait = $pacing->attempt($delivery->endpoint_id, fn () => $deliverer->attempt($delivery, $this->manual));
        } finally {
            $lock->release();
        }

        // Only after letting go of the delivery, a queue that runs jobs right away would otherwise find it locked
        if ($wait !== null) {
            $this->comeBackLater($delivery, $wait);
        }
    }

    /** @return list<string> */
    public function tags(): array
    {
        return ["webhook-delivery:{$this->deliveryId}"];
    }

    /** Queues this attempt again once the endpoint likely has room, each wait a little longer than the last. */
    private function comeBackLater(Delivery $delivery, int $wait): void
    {
        if ($this->waits >= self::MAX_WAITS) {
            $this->handToSweeper();

            return;
        }

        // Keeps the sweeper away while this waits, on the clock of its own kind so a resend never moves a scheduled retry
        $delivery->forceFill([$this->dueColumn() => now()->addSeconds(Webhooks::QUEUE_LEASE_SECONDS)])->save();

        $delay = min(self::MAX_WAIT_SECONDS, $wait + $this->waits * 2) + random_int(0, 2);

        try {
            Bus::dispatch((new self($this->deliveryId, $this->attempts, $this->manual, $this->waits + 1))->delay($delay));
        } catch (Throwable $e) {
            report($e);

            $this->handToSweeper();
        }
    }

    /**
     * Makes the attempt due right away, so `chief:webhooks:dispatch-due` queues it within its limit per endpoint: a
     * resend as a resend, on its own clock. Nothing changes when an attempt happened since this job was queued.
     */
    private function handToSweeper(): void
    {
        Delivery::query()
            ->whereKey($this->deliveryId)
            ->where('attempts', $this->attempts)
            ->update([$this->dueColumn() => now(), 'updated_at' => now()]);
    }

    /** The delivery's due time this attempt follows: resends their own, automatic attempts the retry schedule's. */
    private function dueColumn(): string
    {
        return $this->manual ? 'manual_attempt_at' : 'next_attempt_at';
    }
}
