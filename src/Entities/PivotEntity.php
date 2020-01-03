<?php

namespace IronGate\Chief\Entities;

use Illuminate\Database\Eloquent\Relations\Pivot;
use IronGate\Chief\Concerns\EloquentUserTimezones;

class PivotEntity extends Pivot
{
    use EloquentUserTimezones;
}
