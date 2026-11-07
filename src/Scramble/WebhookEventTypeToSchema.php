<?php

namespace ChiefTools\SDK\Scramble;

use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\ObjectType;
use ChiefTools\SDK\Webhook\Outbound\Webhooks;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Support\Generator\Types as OpenApi;
use ChiefTools\SDK\Webhook\Outbound\Enums\BuiltInEvent;
use ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType;

/**
 * Documents the `WebhookEventType` contract, which the webhooks API resources declare for their event type fields, as
 * the app's event types and the built-in ones: an extensible enum named `WebhookEventType`, since apps add events.
 */
class WebhookEventTypeToSchema extends TypeToSchemaExtension
{
    public const string SCHEMA_NAME = 'WebhookEventType';

    public function shouldHandle(Type $type): bool
    {
        return $type instanceof ObjectType && $type->name === WebhookEventType::class;
    }

    public function toSchema(Type $type): OpenApi\Type
    {
        $types = [...app(Webhooks::class)->eventTypes(), ...BuiltInEvent::cases()];

        $description = collect($types)
            ->map(static fn (WebhookEventType $event): string => "| `{$event->value}` <br/> {$event->description()} |")
            ->prepend('|---|')
            ->prepend('| |')
            ->join("\n");

        $schema = (new OpenApi\StringType)->setDescription($description);
        $schema->setAttribute('casesDescription', $description);
        $schema->setExtensionProperty('extensible-enum', array_map(static fn (WebhookEventType $event): string => (string)$event->value, $types));

        return $schema;
    }

    public function reference(ObjectType $type): Reference
    {
        return new Reference('schemas', WebhookEventType::class, $this->components, self::SCHEMA_NAME);
    }
}
