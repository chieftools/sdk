<?php

namespace ChiefTools\SDK\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

abstract class Job
{
    use Queueable, InteractsWithQueue;
}
