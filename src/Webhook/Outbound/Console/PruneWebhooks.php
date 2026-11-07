<?php

namespace ChiefTools\SDK\Webhook\Outbound\Console;

use Illuminate\Console\Command;
use ChiefTools\SDK\Webhook\Outbound\Models\Event;
use ChiefTools\SDK\Webhook\Outbound\WebhookConfig;
use ChiefTools\SDK\Webhook\Outbound\Models\Delivery;
use ChiefTools\SDK\Webhook\Outbound\Enums\DeliveryStatus;

/**
 * Deletes events older than `chief.webhooks.retention_days` with their deliveries and attempts, in small batches so the
 * tables are never locked for long. Events with a delivery that still has an attempt to make are kept until it is made:
 * a pending delivery, or a resend that is queued or waiting.
 */
class PruneWebhooks extends Command
{
    private const int BATCH_SIZE = 1000;

    protected $hidden = true;

    protected $signature   = 'chief:webhooks:prune';
    protected $description = 'Delete webhook events, deliveries and attempts past their retention.';

    public function handle(): int
    {
        if (!WebhookConfig::enabled()) {
            return self::SUCCESS;
        }

        $cutoff  = now()->subDays(WebhookConfig::retentionDays());
        $deleted = 0;

        do {
            $ids = Event::query()
                ->where('created_at', '<', $cutoff)
                ->whereDoesntHave('deliveries', static fn ($query) => $query->where(static fn ($query) => $query
                    ->where('status', DeliveryStatus::PENDING)
                    ->orWhereNotNull('manual_attempt_at')))
                ->limit(self::BATCH_SIZE)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            // Deleting the deliveries first keeps the cascade per statement small
            Delivery::query()->whereIn('event_id', $ids)->delete();

            $deleted += Event::query()->whereKey($ids)->delete();
        } while ($ids->count() === self::BATCH_SIZE);

        $this->info("Deleted {$deleted} webhook " . str('event')->plural($deleted) . '.');

        return self::SUCCESS;
    }
}
