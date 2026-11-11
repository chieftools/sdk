<?php

namespace ChiefTools\SDK\Webhook\Outbound\Attributes;

use Attribute;

/**
 * Documents what an event sends as `data.object`, see `\ChiefTools\SDK\Scramble\WebhooksDocumentTransformer`.
 *
 * Put it on a case for that event, or on the enum as the default for every case. A case's attribute overrides the
 * enum's type and description, and whatever it leaves out comes from the enum's attribute.
 *
 * The type is a class, like the API resource the payload renders, or a PHPDoc type like `array{id: string}` whose
 * class names resolve against the imports of the enum's file. Without a type the object is documented as any object.
 * The description says what the object is, for apps that send something else than the resource as the API returns it.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT)]
final readonly class WebhookObject
{
    /** What `data.object` is when neither the case nor the enum describes it. */
    public const string DEFAULT_DESCRIPTION = 'The resource the event is about.';

    public function __construct(
        public ?string $type = null,
        public ?string $description = null,
    ) {}
}
