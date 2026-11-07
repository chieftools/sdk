<?php

namespace ChiefTools\SDK\Webhook\Outbound\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use ChiefTools\SDK\Webhook\Outbound\WebhookConfig;

/**
 * The ID a model shows in payloads, the dashboard and APIs: its ULID behind the app's prefix for its kind, see
 * `chief.webhooks.id_prefixes`. Models name their kind in `ID_KIND`.
 */
trait HasFormattedId
{
    public static function idPrefix(): string
    {
        return WebhookConfig::idPrefix(static::ID_KIND);
    }

    public function formattedId(): Attribute
    {
        return new Attribute(
            get: fn () => static::idPrefix() . $this->getKey(),
        );
    }

    /** Finds the model by its formatted ID or its plain ULID. */
    public function scopeWhereFormattedId(Builder $query, string $id): void
    {
        $prefix = static::idPrefix();

        $query->whereKey($prefix !== '' && str_starts_with($id, $prefix) ? substr($id, strlen($prefix)) : $id);
    }
}
