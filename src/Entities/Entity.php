<?php

namespace IronGate\Chief\Entities;

use IronGate\Chief\Concerns\EloquentUserTimezones;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Entity extends Eloquent
{
    use EloquentUserTimezones;

    protected $visible = ['id'];
}
