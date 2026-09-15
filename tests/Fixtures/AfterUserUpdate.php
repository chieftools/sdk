<?php

namespace Tests\Fixtures;

use ChiefTools\SDK\Entities\User;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class AfterUserUpdate implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
    ) {}

    public function handle(): void {}
}
