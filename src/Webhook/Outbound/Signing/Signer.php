<?php

namespace ChiefTools\SDK\Webhook\Outbound\Signing;

use InvalidArgumentException;

/**
 * Signs deliveries the Standard Webhooks way (https://www.standardwebhooks.com): an HMAC-SHA256 of
 * `{id}.{timestamp}.{body}` with the base64 decoded secret, so receivers can verify with any of its libraries.
 */
final class Signer
{
    public const string SECRET_PREFIX = 'whsec_';

    private const int SECRET_BYTES = 24;

    public static function generateSecret(): string
    {
        return self::SECRET_PREFIX . base64_encode(random_bytes(self::SECRET_BYTES));
    }

    /**
     * The `webhook-signature` header: a `v1,{base64}` signature per secret, separated by spaces.
     *
     * @param list<string> $secrets
     */
    public static function signature(string $id, int $timestamp, string $body, array $secrets): string
    {
        if ($secrets === []) {
            throw new InvalidArgumentException('Signing needs at least one secret.');
        }

        return implode(' ', array_map(
            static fn (string $secret): string => 'v1,' . base64_encode(hash_hmac('sha256', "{$id}.{$timestamp}.{$body}", self::decodeSecret($secret), true)),
            $secrets,
        ));
    }

    /**
     * The headers a delivery is signed with.
     *
     * @param list<string> $secrets
     *
     * @return array{webhook-id: string, webhook-timestamp: string, webhook-signature: string}
     */
    public static function headers(string $id, int $timestamp, string $body, array $secrets): array
    {
        return [
            'webhook-id'        => $id,
            'webhook-timestamp' => (string)$timestamp,
            'webhook-signature' => self::signature($id, $timestamp, $body, $secrets),
        ];
    }

    private static function decodeSecret(string $secret): string
    {
        $decoded = base64_decode(str_starts_with($secret, self::SECRET_PREFIX) ? substr($secret, strlen(self::SECRET_PREFIX)) : $secret, true);

        if ($decoded === false || $decoded === '') {
            throw new InvalidArgumentException('The signing secret is not valid base64.');
        }

        return $decoded;
    }
}
