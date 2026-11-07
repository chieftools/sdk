<?php

namespace ChiefTools\SDK\Webhook\Outbound\Security;

use Closure;
use IPLib\Factory;
use IPLib\Range\Type;
use ChiefTools\SDK\Webhook\Outbound\WebhookConfig;
use ChiefTools\SDK\Webhook\Outbound\Exceptions\BlockedUrl;

/**
 * Keeps webhooks from reaching the app's own network: URLs must use HTTPS and every address their host resolves to
 * must be public. Checked when an endpoint is saved and again before every attempt.
 */
class UrlGuard
{
    /**
     * Ranges that are not routable on the internet but that ip-lib counts as public.
     *
     * @var list<string>
     */
    private const array UNROUTABLE_RANGES = [
        // IPv6 documentation
        '2001:db8::/32',
    ];

    /** @var \Closure(string): list<string> */
    private Closure $resolver;

    /** @param (\Closure(string): list<string>)|null $resolver resolves a host name to its IPv4 and IPv6 addresses */
    public function __construct(?Closure $resolver = null)
    {
        $this->resolver = $resolver ?? self::resolveWithDns(...);
    }

    /** @throws \ChiefTools\SDK\Webhook\Outbound\Exceptions\BlockedUrl */
    public function resolve(string $url): ResolvedUrl
    {
        $parts = parse_url($url);

        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            throw new BlockedUrl('The URL is not valid.');
        }

        $scheme = strtolower($parts['scheme']);

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new BlockedUrl('The URL cannot contain credentials.');
        }

        $allowPrivate = WebhookConfig::allowsPrivateAddresses();

        if ($scheme !== 'https' && !($allowPrivate && $scheme === 'http')) {
            throw new BlockedUrl('The URL must use HTTPS.');
        }

        $host = strtolower($parts['host']);
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        if ($allowPrivate) {
            return new ResolvedUrl($url, $host, $port, null);
        }

        $literal = trim($host, '[]');

        $addresses = filter_var($literal, FILTER_VALIDATE_IP) !== false
            ? [$literal]
            : ($this->resolver)($host);

        if ($addresses === []) {
            throw new BlockedUrl("The host {$host} does not resolve.");
        }

        foreach ($addresses as $address) {
            if (!self::isPublicAddress($address)) {
                throw new BlockedUrl("The host {$host} resolves to a private or reserved address.");
            }
        }

        return new ResolvedUrl($url, $host, $port, $addresses[0]);
    }

    /**
     * Whether an address is on the public internet, judged by the IANA special-purpose registries through ip-lib. An
     * IPv4 address embedded in IPv6, like `::ffff:10.0.0.1` or 6to4, is judged as the IPv4 address it reaches.
     */
    public static function isPublicAddress(string $ip): bool
    {
        $address = Factory::parseAddressString($ip);

        if ($address === null || $address->getRangeType() !== Type::T_PUBLIC) {
            return false;
        }

        foreach (self::UNROUTABLE_RANGES as $range) {
            if (Factory::parseRangeString($range)?->contains($address)) {
                return false;
            }
        }

        return true;
    }

    /** @return list<string> */
    private static function resolveWithDns(string $host): array
    {
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);

        if ($records === false) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (array $record): ?string => $record['ip'] ?? $record['ipv6'] ?? null,
            $records,
        )));
    }
}
