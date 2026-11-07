<?php

namespace ChiefTools\SDK\Webhook\Outbound\Models;

use ChiefTools\SDK\Entities\Entity;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ChiefTools\SDK\Webhook\Outbound\Enums\AttemptError;

/**
 * One HTTP request of a delivery and what the endpoint answered, the body sent is the event's payload.
 *
 * @property string                                                   $id
 * @property string                                                   $delivery_id
 * @property string                                                   $endpoint_id
 * @property int                                                      $number
 * @property bool                                                     $manual
 * @property bool                                                     $succeeded
 * @property array<string, string>                                    $request_headers
 * @property int|null                                                 $response_status
 * @property array<string, string>|null                               $response_headers
 * @property string|null                                              $response_body
 * @property bool                                                     $response_truncated
 * @property int|null                                                 $duration_ms
 * @property \ChiefTools\SDK\Webhook\Outbound\Enums\AttemptError|null $error
 * @property string|null                                              $error_message
 * @property \Carbon\Carbon                                           $attempted_at
 * @property \ChiefTools\SDK\Webhook\Outbound\Models\Delivery         $delivery
 * @property \ChiefTools\SDK\Webhook\Outbound\Models\Endpoint         $endpoint
 */
class Attempt extends Entity
{
    use HasUlids;

    /** Dates with microseconds, which `attempted_at` and an endpoint's `failing_since` are stored with. */
    public const string DATE_FORMAT = 'Y-m-d H:i:s.u';

    public $timestamps = false;

    /** Keeps the microseconds of `attempted_at`, attempts of one endpoint are ordered by it. */
    protected $dateFormat = self::DATE_FORMAT;

    protected $table   = 'webhook_delivery_attempts';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'number'             => 'int',
            'manual'             => 'bool',
            'succeeded'          => 'bool',
            'request_headers'    => 'array',
            'response_status'    => 'int',
            'response_headers'   => 'array',
            'response_truncated' => 'bool',
            'duration_ms'        => 'int',
            'error'              => AttemptError::class,
            'attempted_at'       => 'datetime',
        ];
    }

    // Relations
    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\ChiefTools\SDK\Webhook\Outbound\Models\Delivery, $this> */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }
    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\ChiefTools\SDK\Webhook\Outbound\Models\Endpoint, $this> */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }
}
