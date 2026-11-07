<?php

namespace Tests\Fixtures\Webhooks;

use Dedoc\Scramble\Attributes\SchemaName;

#[SchemaName('LanternColor')]
enum LanternColor: string
{
    case AMBER = 'amber';
    case WHITE = 'white';
}
