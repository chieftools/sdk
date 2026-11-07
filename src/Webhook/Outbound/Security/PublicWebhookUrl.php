<?php

namespace ChiefTools\SDK\Webhook\Outbound\Security;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use ChiefTools\SDK\Webhook\Outbound\Exceptions\BlockedUrl;

/**
 * Validates an endpoint URL with `\ChiefTools\SDK\Webhook\Outbound\Security\UrlGuard`.
 */
class PublicWebhookUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The :attribute must be a URL.');

            return;
        }

        try {
            app(UrlGuard::class)->resolve($value);
        } catch (BlockedUrl $e) {
            $fail($e->getMessage());
        }
    }
}
