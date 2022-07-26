<?php

namespace ChiefTools\SDK\Entities;

use ChiefTools\SDK\Concerns\EloquentUserTimezones;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Entity extends Eloquent
{
    use EloquentUserTimezones;

    protected $visible = ['id'];
}
