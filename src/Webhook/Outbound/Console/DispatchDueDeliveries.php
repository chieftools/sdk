<?php

namespace ChiefTools\SDK\Webhook\Outbound\Console;

use Throwable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use ChiefTools\SDK\Webhook\Outbound\Webhooks;
use ChiefTools\SDK\Webhook\Outbound\WebhookConfig;
use ChiefTools\SDK\Webhook\Outbound\Models\Delivery;
use ChiefTools\SDK\Webhook\Outbound\Jobs\SendDelivery;
use ChiefTools\SDK\Webhook\Outbound\Enums\DeliveryStatus;

/**
 * Queues the attempts that are due: retries and resends whose waiting is over, and attempts whose job got lost before it
 * ran. At most `MAX_PER_ENDPOINT` per endpoint each run, the rest stays due for the next, so catching up after an outage
 * reaches a receiver spread over the minutes instead of all at once.
 */
class DispatchDueDeliveries extends Command
{
    private const int BATCH_SIZE = 500;

    public const int MAX_PER_ENDPOINT = 50;

    protected $hidden = true;

    protected $signature   = 'chief:webhooks:dispatch-due';
    protected $description = 'Queue the webhook delivery attempts that are due.';

    private int $queued = 0;

    /** @var array<string, int> */
    private array $perEndpoint = [];

    /** @var list<string> endpoints that reached their share of this run */
    private array $full = [];

    public function handle(): int
    {
        if (!WebhookConfig::enabled()) {
            return self::SUCCESS;
        }

        // Automatic retries follow the retry schedule of pending deliveries, resends their own clock whatever the state
        foreach ([false, true] as $manual) {
            try {
                $this->queueDue($manual);
            } catch (Throwable $e) {
                report($e);

                $this->error("Queued {$this->queued} webhook " . str('delivery')->plural($this->queued) . ' before the queue failed: ' . $e->getMessage());

                return self::FAILURE;
            }
        }

        $this->info("Queued {$this->queued} webhook " . str('delivery')->plural($this->queued) . '.');

        return self::SUCCESS;
    }

    /** @throws \Throwable when the queue fails, after handing the claim of that attempt back */
    private function queueDue(bool $manual): void
    {
        $column = $manual ? 'manual_attempt_at' : 'next_attempt_at';

        do {
            $due = Delivery::query()
                ->when(!$manual, static fn ($query) => $query->where('status', DeliveryStatus::PENDING))
                ->where($column, '<=', now())
                ->whereNotIn('endpoint_id', $this->full)
                ->orderBy($column)
                ->limit(self::BATCH_SIZE)
                ->get(['id', 'endpoint_id', 'attempts', $column]);

            foreach ($due as $delivery) {
                if (in_array($delivery->endpoint_id, $this->full, true)) {
                    continue;
                }

                // Claim it by pushing its due time ahead, only when no other run claimed it in the meantime
                $claimed = Delivery::query()
                    ->whereKey($delivery->getKey())
                    ->where($column, $delivery->getRawOriginal($column))
                    ->update([$column => now()->addSeconds(Webhooks::QUEUE_LEASE_SECONDS)]);

                if ($claimed !== 1) {
                    continue;
                }

                try {
                    Bus::dispatch(new SendDelivery($delivery->getKey(), $delivery->attempts, manual: $manual));
                } catch (Throwable $e) {
                    // Hand the claim back so the next run queues it
                    Delivery::query()->whereKey($delivery->getKey())->update([$column => $delivery->getRawOriginal($column)]);

                    throw $e;
                }

                $this->queued++;

                $this->perEndpoint[$delivery->endpoint_id] = ($this->perEndpoint[$delivery->endpoint_id] ?? 0) + 1;

                if ($this->perEndpoint[$delivery->endpoint_id] >= self::MAX_PER_ENDPOINT) {
                    $this->full[] = $delivery->endpoint_id;
                }
            }
        } while ($due->count() === self::BATCH_SIZE);
    }
}
