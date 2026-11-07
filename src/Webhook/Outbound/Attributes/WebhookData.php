<?php

namespace ChiefTools\SDK\Webhook\Outbound\Attributes;

use Attribute;

/**
 * Documents a field an event case sends next to the object in `data`, see
 * `\ChiefTools\SDK\Scramble\WebhooksDocumentTransformer`.
 *
 * The type is a class, like `\Carbon\CarbonInterface` for a date and time or an enum, or a PHPDoc type like
 * `list<array{subject: string}>` whose class names resolve against the imports of the enum's file.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT | Attribute::IS_REPEATABLE)]
final readonly class WebhookData
{
    public function __construct(
        public string $name,
        public string $type,
        public string $description,
        public bool $required = true,
    ) {}
}
