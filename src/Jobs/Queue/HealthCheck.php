<?php

namespace IronGate\Chief\Jobs\Queue;

use Exception;
use GuzzleHttp\Client;
use IronGate\Chief\Jobs\Job;
use Illuminate\Contracts\Queue\ShouldQueue;

class HealthCheck extends Job implements ShouldQueue
{
    public function handle(Client $client): void
    {
        try {
            retry(3, static function () use ($client) {
                $client->get(config('chief.queue.monitor'));
            }, 10);
        } catch (Exception) {
            // We try again later if we cannot reach the monitor!
        }
    }
}
