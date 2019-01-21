<?php

namespace IronGate\Integration\Console\Commands;

use Illuminate\Console\Command;
use IronGate\Integration\Jobs\Queue\HealthCheck;

class QueueHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:health-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch a queued job to validate it\'s processing';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (is_null(config('queue.monitor'))) {
            $this->warn('Queue not being monitored because monitor webhook is not set.');

            return;
        }

        dispatch(new HealthCheck);

        $this->info('Fired job onto the queue... standby for transmission!');
    }
}
