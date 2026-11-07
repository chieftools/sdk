<?php

namespace ChiefTools\SDK\Webhook\Outbound\Models;

use ChiefTools\SDK\Chief;
use ChiefTools\SDK\Entities\Entity;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ChiefTools\SDK\Webhook\Outbound\Enums\DeliveryStatus;
use ChiefTools\SDK\Webhook\Outbound\Models\Concerns\HasFormattedId;

/**
 * One event sent to one endpoint, retried on the schedule in `chief.webhooks.retry_schedule` until it succeeds.
 *
 * @property string                                                                                         $id
 * @property int                                                                                            $team_id
 * @property string                                                                                         $endpoint_id
 * @property string                                                                                         $event_id
 * @property \ChiefTools\SDK\Webhook\Outbound\Enums\DeliveryStatus                                          $status
 * @property int                                                                                            $attempts
 * @property \Carbon\Carbon|null                                                                            $next_attempt_at
 * @property \Carbon\Carbon|null                                                                            $manual_attempt_at
 * @property \Carbon\Carbon|null                                                                            $last_attempted_at
 * @property int|null                                                                                       $last_response_status
 * @property \Carbon\Carbon                                                                                 $created_at
 * @property \Carbon\Carbon                                                                                 $updated_at
 * @property string                                                                                         $formatted_id
 * @property \ChiefTools\SDK\Entities\Team                                                                  $team
 * @property \ChiefTools\SDK\Webhook\Outbound\Models\Endpoint                                               $endpoint
 * @property \ChiefTools\SDK\Webhook\Outbound\Models\Event                                                  $event
 * @property \Illuminate\Database\Eloquent\Collection<int, \ChiefTools\SDK\Webhook\Outbound\Models\Attempt> $attemptLog
 */
class Delivery extends Entity
{
    use HasFormattedId, HasUlids;

    /** Its kind in `chief.webhooks.id_prefixes`. */
    public const string ID_KIND = 'delivery';

    protected $table   = 'webhook_deliveries';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status'            => DeliveryStatus::class,
            'attempts'          => 'int',
            'next_attempt_at'   => 'datetime',
            'manual_attempt_at' => 'datetime',
            'last_attempted_at' => 'datetime',
        ];
    }

    // Attributes
    public function isPending(): bool
    {
        return $this->status === DeliveryStatus::PENDING;
    }

    // Relations
    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\ChiefTools\SDK\Entities\Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Chief::teamModel());
    }
    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\ChiefTools\SDK\Webhook\Outbound\Models\Endpoint, $this> */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }
    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\ChiefTools\SDK\Webhook\Outbound\Models\Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<\ChiefTools\SDK\Webhook\Outbound\Models\Attempt, $this> */
    public function attemptLog(): HasMany
    {
        return $this->hasMany(Attempt::class)->orderBy('number');
    }
}
