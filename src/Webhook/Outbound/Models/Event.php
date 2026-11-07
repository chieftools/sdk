<?php

namespace ChiefTools\SDK\Webhook\Outbound\Models;

use ChiefTools\SDK\Chief;
use ChiefTools\SDK\Entities\Entity;
use ChiefTools\SDK\Webhook\Outbound\WebhookConfig;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ChiefTools\SDK\Webhook\Outbound\Enums\BuiltInEvent;
use ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType;
use ChiefTools\SDK\Webhook\Outbound\Models\Concerns\HasFormattedId;

/**
 * Something that happened which the app sent to the team's subscribed endpoints, with the exact body they receive.
 *
 * @property string                                                                                          $id
 * @property int                                                                                             $team_id
 * @property string                                                                                          $type
 * @property string                                                                                          $api_version
 * @property string                                                                                          $payload
 * @property \Carbon\Carbon                                                                                  $created_at
 * @property string                                                                                          $formatted_id
 * @property \ChiefTools\SDK\Entities\Team                                                                   $team
 * @property \Illuminate\Database\Eloquent\Collection<int, \ChiefTools\SDK\Webhook\Outbound\Models\Delivery> $deliveries
 */
class Event extends Entity
{
    use HasFormattedId, HasUlids;

    /** Its kind in `chief.webhooks.id_prefixes`. */
    public const string ID_KIND = 'event';

    public const null UPDATED_AT = null;

    protected $table   = 'webhook_events';
    protected $guarded = [];

    // Attributes
    /** The event's case in the app's enum, or a built-in event, null when the app no longer has it. */
    public function eventType(): ?WebhookEventType
    {
        $enum = WebhookConfig::eventsEnum();

        return BuiltInEvent::tryFrom($this->type) ?? ($enum === null ? null : $enum::tryFrom($this->type));
    }

    /**
     * The decoded body, for display.
     *
     * @return array<string, mixed>
     */
    public function decodedPayload(): array
    {
        return json_decode($this->payload, true, flags: JSON_THROW_ON_ERROR);
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
