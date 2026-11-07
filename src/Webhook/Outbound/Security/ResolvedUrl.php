<?php

namespace ChiefTools\SDK\Webhook\Outbound\Security;

/**
 * A webhook URL with the address it was checked against, requests connect to that address so a DNS change between the
 * check and the request cannot point it at a private one.
 */
final readonly class ResolvedUrl
{
    public function __construct(
        public string $url,
        public string $host,
        public int $port,
        public ?string $ip,
    ) {}

    /**
     * The cURL option that connects to the checked address, none when the host is an IP or checks are off.
     *
     * @return array<int, list<string>>
     */
    public function curlOptions(): array
    {
        if ($this->ip === null || $this->ip === trim($this->host, '[]')) {
            return [];
        }

        $ip = str_contains($this->ip, ':') ? "[{$this->ip}]" : $this->ip;

        return [CURLOPT_RESOLVE => ["{$this->host}:{$this->port}:{$ip}"]];
    }
}
