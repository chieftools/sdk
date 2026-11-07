<?php

namespace ChiefTools\SDK\Webhook\Outbound\Actions;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use ChiefTools\SDK\Webhook\Outbound\Webhooks;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;
use ChiefTools\SDK\Webhook\Outbound\Security\PublicWebhookUrl;

/**
 * Validates what a team sets on an endpoint, shared by the dashboard and API requests of apps.
 */
final class EndpointInput
{
    public const int MAX_URL_LENGTH = 2048;

    public const int MAX_DESCRIPTION_LENGTH = 255;

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        return [
            'url'         => self::urlRules(),
            'description' => ['nullable', 'string', 'max:' . self::MAX_DESCRIPTION_LENGTH],
            'events'      => ['required', 'array', 'min:1'],
            'events.*'    => ['required', 'string', 'distinct', Rule::in(self::allowedEvents())],
        ];
    }

    /** @return list<mixed> */
    public static function urlRules(): array
    {
        return ['required', 'string', 'max:' . self::MAX_URL_LENGTH, 'url:http,https', new PublicWebhookUrl];
    }

    /** @return list<string> */
    public static function allowedEvents(): array
    {
        return [
            Endpoint::ALL_EVENTS,
            ...array_map(static fn ($type) => $type->value, app(Webhooks::class)->eventTypes()),
        ];
    }

    /**
     * @param array<string, mixed> $input
     *
     * @throws \Illuminate\Validation\ValidationException
     *
     * @return array{url: string, description: string|null, events: list<string>}
     */
    public static function validate(array $input): array
    {
        if (is_string($input['url'] ?? null)) {
            $input['url'] = trim($input['url']);
        }

        $validated = Validator::make($input, self::rules())->validate();

        $events = array_values(array_unique($validated['events']));

        return [
            'url'         => $validated['url'],
            'description' => filled($validated['description'] ?? null) ? trim($validated['description']) : null,
            // Every event already includes the specific ones
            'events' => in_array(Endpoint::ALL_EVENTS, $events, true) ? [Endpoint::ALL_EVENTS] : $events,
        ];
    }
}
