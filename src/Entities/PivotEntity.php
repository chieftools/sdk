<?php

namespace ChiefTools\SDK\Entities;

use Illuminate\Database\Eloquent\Relations\Pivot;
use ChiefTools\SDK\Concerns\EloquentUserTimezones;

class PivotEntity extends Pivot
{
    use EloquentUserTimezones;
}
