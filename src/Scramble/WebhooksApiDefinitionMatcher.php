<?php

namespace ChiefTools\SDK\Scramble;

use Dedoc\Scramble\Infer\Configuration\DefinitionMatcher;

/**
 * The controllers, requests and resources of the webhooks API. Scramble infers types from the source of classes
 * outside `vendor` only, so apps that mount the API would otherwise document its responses without their fields.
 */
class WebhooksApiDefinitionMatcher implements DefinitionMatcher
{
    public const string NAMESPACE = 'ChiefTools\\SDK\\Webhook\\Outbound\\Http\\';

    public function matches(string $class): bool
    {
        return str_starts_with(ltrim($class, '\\'), self::NAMESPACE);
    }
}
