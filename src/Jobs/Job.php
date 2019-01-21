<?php

namespace IronGate\Integration\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

abstract class Job
{
    use Dispatchable, Queueable, InteractsWithQueue;
}
