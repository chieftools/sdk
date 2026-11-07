<?php

namespace ChiefTools\SDK\Webhook\Outbound;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Paces the requests one endpoint gets: at most `chief.webhooks.max_concurrent_per_endpoint` at the same time and
 * `chief.webhooks.max_per_second_per_endpoint` per second. A slow endpoint then holds only a few queue workers, and a
 * burst of events reaches a small receiver spread out.
 *
 * Both limits rely on the default cache store's locks and atomic increments, which Redis, Memcached and the database
 * store provide. The file and array stores increment by reading and writing, so workers counting at the same moment
 * can each let a request through. That is accepted rather than paying for an extra lock on every delivery.
 */
class EndpointPacing
{
    /** A slot of a worker that died is free again after this, longer than any attempt takes. */
    private const int SLOT_TIMEOUT_SECONDS = Deliverer::TIMEOUT_SECONDS + 30;

    /**
     * Sends to the endpoint when it has room.
     *
     * @return int|null null when it was sent, otherwise the seconds to wait before trying again
     */
    public function attempt(string $endpointId, Closure $send): ?int
    {
        $rateKey     = "chief-webhooks:endpoint-rate:{$endpointId}";
        $perSecond   = WebhookConfig::maxPerSecondPerEndpoint();
        $concurrency = WebhookConfig::maxConcurrentPerEndpoint();

        return Cache::funnel("chief-webhooks:endpoint-concurrency:{$endpointId}")
            ->limit($concurrency)
            ->releaseAfter(self::SLOT_TIMEOUT_SECONDS)
            ->block(0)
            ->then(
                static function () use ($send, $rateKey, $perSecond): ?int {
                    // Counting first is one atomic increment, so workers at the same moment cannot both pass a check
                    // that runs before either counts. A request that does not fit counts too, which errs on the slow side
                    if (RateLimiter::hit($rateKey, 1) > $perSecond) {
                        return max(1, RateLimiter::availableIn($rateKey));
                    }

                    $send();

                    return null;
                },
                // Every slot is taken by a request still waiting for its response
                static fn (): int => 1,
            );
    }
}
