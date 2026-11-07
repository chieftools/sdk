<?php

namespace ChiefTools\SDK\Webhook\Outbound;

use UnitEnum;
use RuntimeException;
use ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType;

/**
 * Reads the `chief.webhooks` config with its defaults, apps that publish the config only keep the keys they set
 * because the SDK merges its config one level deep.
 */
final class WebhookConfig
{
    /** @var list<int> */
    public const array DEFAULT_RETRY_SCHEDULE = [60, 300, 1800, 7200, 21600, 43200, 86400, 86400];

    /** @var array{endpoint: string, event: string, delivery: string} */
    public const array DEFAULT_ID_PREFIXES = [
        'endpoint' => 'webhook_',
        'event'    => 'event_',
        'delivery' => 'delivery_',
    ];

    public static function enabled(): bool
    {
        return (bool)config('chief.webhooks.enabled', false);
    }

    /** @return class-string<\ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType>|null */
    public static function eventsEnum(): ?string
    {
        $enum = config('chief.webhooks.events');

        if ($enum === null) {
            return null;
        }

        if (!is_string($enum) || !enum_exists($enum) || !is_subclass_of($enum, WebhookEventType::class)) {
            throw new RuntimeException('The chief.webhooks.events config must name a backed enum implementing ' . WebhookEventType::class . '.');
        }

        return $enum;
    }

    public static function queue(): ?string
    {
        $queue = config('chief.webhooks.queue');

        return $queue === null ? null : (string)($queue instanceof UnitEnum ? enum_value($queue) : $queue);
    }

    public static function apiVersion(): string
    {
        return (string)config('chief.webhooks.api_version', 'v1');
    }

    public static function userAgent(): string
    {
        return (string)(config('chief.webhooks.user_agent') ?? str((string)config('app.name', 'Chief'))->studly() . '-Webhooks/1.0');
    }

    public static function retentionDays(): int
    {
        return max(1, (int)config('chief.webhooks.retention_days', 30));
    }

    /** @return list<int> */
    public static function retrySchedule(): array
    {
        $schedule = config('chief.webhooks.retry_schedule', self::DEFAULT_RETRY_SCHEDULE);

        return array_values(array_map(intval(...), is_array($schedule) ? $schedule : self::DEFAULT_RETRY_SCHEDULE));
    }

    /** The attempts a delivery gets before it fails: the first and one after every wait in the schedule. */
    public static function maxAttempts(): int
    {
        return count(self::retrySchedule()) + 1;
    }

    public static function disableAfterDays(): int
    {
        return max(1, (int)config('chief.webhooks.disable_after_days', 5));
    }

    /** Requests one endpoint gets at the same time. */
    public static function maxConcurrentPerEndpoint(): int
    {
        return max(1, (int)config('chief.webhooks.max_concurrent_per_endpoint', 2));
    }

    /** Requests one endpoint gets per second. */
    public static function maxPerSecondPerEndpoint(): int
    {
        return max(1, (int)config('chief.webhooks.max_per_second_per_endpoint', 10));
    }

    public static function allowsPrivateAddresses(): bool
    {
        return (bool)config('chief.webhooks.allow_private_addresses', false);
    }

    /**
     * The app page the webhooks pages sit under in their breadcrumbs, like its settings, none by default.
     *
     * @return array{title: string, route: string}|null
     */
    public static function breadcrumbParent(): ?array
    {
        $parent = config('chief.webhooks.breadcrumb_parent');

        return is_array($parent) && isset($parent['title'], $parent['route']) ? ['title' => (string)$parent['title'], 'route' => (string)$parent['route']] : null;
    }

    /**
     * The prefix of the IDs of a kind of model, matching how the app formats IDs in its API: `event_01…` by default,
     * an empty prefix for apps whose IDs are plain ULIDs.
     *
     * @param 'endpoint'|'event'|'delivery' $kind
     */
    public static function idPrefix(string $kind): string
    {
        $prefixes = config('chief.webhooks.id_prefixes');

        return (string)(is_array($prefixes) && array_key_exists($kind, $prefixes) ? $prefixes[$kind] : self::DEFAULT_ID_PREFIXES[$kind]);
    }
}
