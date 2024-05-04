<?php

namespace ChiefTools\SDK\Entities;

use ChiefTools\SDK\Concerns\EloquentUserTimezones;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Entity extends Eloquent
{
    use EloquentUserTimezones;

    protected $visible = ['id'];

    public function getQueueableRelations(): array
    {
        // Queueable relations rarely have a benefit and mostly have negative effect, so we disable them by default
        return [];
    }
}
