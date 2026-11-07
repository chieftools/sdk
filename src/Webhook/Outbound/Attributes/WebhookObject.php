<?php

namespace ChiefTools\SDK\Webhook\Outbound\Attributes;

use Attribute;

/**
 * Documents what an event case sends as `data.object`, see `\ChiefTools\SDK\Scramble\WebhooksDocumentTransformer`.
 *
 * The type is a class, like the API resource the payload renders, or a PHPDoc type like `array{id: string}` whose
 * class names resolve against the imports of the enum's file.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
final readonly class WebhookObject
{
    public function __construct(
        public string $type,
    ) {}
}
