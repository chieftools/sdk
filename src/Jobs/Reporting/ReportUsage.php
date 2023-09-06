<?php

namespace ChiefTools\SDK\Jobs\Reporting;

use ChiefTools\SDK\Jobs\Job;
use ChiefTools\SDK\API\Client;
use Illuminate\Contracts\Queue\ShouldQueue;

class ReportUsage extends Job implements ShouldQueue
{
    public function __construct(
        private readonly string $teamSlug,
        private readonly string $usageId,
        private readonly int $usage,
    ) {}

    public function handle(Client $mothership): void
    {
        $mothership->reportUsage($this->teamSlug, $this->usageId, $this->usage);
    }
}
