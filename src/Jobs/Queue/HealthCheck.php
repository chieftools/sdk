<?php

namespace IronGate\Integration\Jobs\Queue;

use Exception;
use GuzzleHttp\Client;
use IronGate\Integration\Jobs\Job;
use Illuminate\Contracts\Queue\ShouldQueue;

class HealthCheck extends Job implements ShouldQueue
{
    /**
     * Handle the job.
     *
     * @param \GuzzleHttp\Client $client
     */
    public function handle(Client $client)
    {
        try {
            retry(3, function () use ($client) {
                $client->get(config('queue.monitor'));
            }, 10);
        } catch (Exception $e) {
            // We try again later if we cannot reach the monitor!
        }
    }
}
