<?php

namespace IronGate\Chief\Jobs\Queue;

use Exception;
use IronGate\Chief\Jobs\Job;
use Illuminate\Contracts\Queue\ShouldQueue;

class HealthCheck extends Job implements ShouldQueue
{
    public function handle(): void
    {
        $client = http(timeout: 3);

        try {
            retry(3, static fn () => $client->get(config('chief.queue.monitor')), 100);
        } catch (Exception) {
            // We try again later if we cannot reach the monitor!
        }
    }
}
