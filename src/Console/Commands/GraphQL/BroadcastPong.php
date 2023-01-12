<?php

namespace ChiefTools\SDK\Console\Commands\GraphQL;

use Illuminate\Console\Command;
use ChiefTools\SDK\GraphQL\Subscriptions;

class BroadcastPong extends Command
{
    protected $signature   = <<<COMMAND
                             chief:graphql:broadcast:ping
                             COMMAND;
    protected $description = 'Broadcast to the ping subscribers.';

    public function handle(): void
    {
        Subscriptions\Ping::dispatch('pong', false);
    }
}
