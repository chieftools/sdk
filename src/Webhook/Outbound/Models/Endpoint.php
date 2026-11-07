<?php

namespace ChiefTools\SDK\Webhook\Outbound\Models;

use ChiefTools\SDK\Chief;
use ChiefTools\SDK\Entities\Entity;
use ChiefTools\SDK\Webhook\Outbound\Webhooks;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ChiefTools\SDK\Webhook\Outbound\Enums\DisabledReason;
use ChiefTools\SDK\Webhook\Outbound\Enums\EndpointStatus;
use ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType;
use ChiefTools\SDK\Webhook\Outbound\Models\Concerns\HasFormattedId;

/**
 * A URL a team receives webhooks on, see `\ChiefTools\SDK\Webhook\Outbound\Webhooks`.
 *
 * @property string                                                                                          $id
 * @property int                                                                                             $team_id
 * @property string                                                                                          $url
 * @property string|null                                                                                     $description
 * @property list<string>                                                                                    $events
 * @property string                                                                                          $secret
 * @property string|null                                                                                     $previous_secret
 * @property \Carbon\Carbon|null                                                                             $previous_secret_expires_at
 * @property \ChiefTools\SDK\Webhook\Outbound\Enums\EndpointStatus                                           $status
 * @property \ChiefTools\SDK\Webhook\Outbound\Enums\DisabledReason|null                                      $disabled_reason
 * @property \Carbon\Carbon|null                                                                             $disabled_at
 * @property \Carbon\Carbon|null                                                                             $failing_since
 * @property \Carbon\Carbon|null                                                                             $failure_notified_at
 * @property \Carbon\Carbon                                                                                  $created_at
 * @property \Carbon\Carbon                                                                                  $updated_at
 * @property string                                                                                          $formatted_id
 * @property \ChiefTools\SDK\Entities\Team                                                                   $team
 * @property \Illuminate\Database\Eloquent\Collection<int, \ChiefTools\SDK\Webhook\Outbound\Models\Delivery> $deliveries
 */
class Endpoint extends Entity
{
    use HasFormattedId, HasUlids;

    /** Subscribing to this receives every event, including ones added later. */
    public const string ALL_EVENTS = '*';

    /** Its kind in `chief.webhooks.id_prefixes`. */
    public const string ID_KIND = 'endpoint';

    /** Keeps the microseconds of `failing_since`, it is compared with when attempts were sent. */
    protected $dateFormat = Attempt::DATE_FORMAT;

    protected $table   = 'webhook_endpoints';
    protected $guarded = [];

    protected $hidden = [
        'secret',
        'previous_secret',
    ];

    protected function casts(): array
    {
        return [
            'events'                     => 'array',
            'secret'                     => 'encrypted',
            'previous_secret'            => 'encrypted',
            'previous_secret_expires_at' => 'datetime',
            'status'                     => EndpointStatus::class,
            'disabled_reason'            => DisabledReason::class,
            'disabled_at'                => 'datetime',
            'failing_since'              => 'datetime',
            'failure_notified_at'        => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Only once the change commits, another connection could otherwise cache the old list again in the meantime
        $forget = static fn (self $endpoint) => $endpoint->getConnection()->afterCommit(
            static fn () => app(Webhooks::class)->forgetEndpoints($endpoint->team_id),
        );

        static::saved($forget);
        static::deleted($forget);
    }

    // Attributes
    public function isEnabled(): bool
    {
        return $this->status === EndpointStatus::ENABLED;
    }

    public function receivesAllEvents(): bool
    {
        return in_array(self::ALL_EVENTS, $this->events ?? [], true);
    }

    public function isSubscribedTo(WebhookEventType|string $type): bool
    {
        $type = $type instanceof WebhookEventType ? $type->value : $type;

        return $this->receivesAllEvents() || in_array($type, $this->events ?? [], true);
    }

    /** Whether the secret from before the last rotation still signs deliveries. */
    public function hasActivePreviousSecret(): bool
    {
        return $this->previous_secret !== null && $this->previous_secret_expires_at?->isFuture() === true;
    }

    /**
     * The secrets deliveries are signed with now: the current one and the previous one until it expires.
     *
     * @return list<string>
     */
    public function signingSecrets(): array
    {
        return $this->hasActivePreviousSecret() ? [$this->secret, $this->previous_secret] : [$this->secret];
    }

    // Relations
    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\ChiefTools\SDK\Entities\Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Chief::teamModel());
    }
    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<\ChiefTools\SDK\Webhook\Outbound\Models\Delivery, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }
}
