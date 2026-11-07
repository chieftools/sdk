<?php

use ChiefTools\SDK\Webhook\Outbound\Security\UrlGuard;
use ChiefTools\SDK\Webhook\Outbound\Exceptions\BlockedUrl;

it('rejects addresses that are not public', function (string $ip) {
    expect(UrlGuard::isPublicAddress($ip))->toBeFalse();
})->with([
    'loopback'           => '127.0.0.1',
    'private 10/8'       => '10.1.2.3',
    'private 172.16/12'  => '172.20.0.1',
    'private 192.168/16' => '192.168.1.10',
    'link local'         => '169.254.169.254',
    'carrier-grade NAT'  => '100.64.0.1',
    'this network'       => '0.0.0.0',
    'documentation'      => '203.0.113.7',
    'multicast'          => '224.0.0.1',
    'IPv6 loopback'      => '::1',
    'IPv6 unique local'  => 'fd12:3456:789a::1',
    'IPv6 link local'    => 'fe80::1',
    'IPv4-mapped IPv6'   => '::ffff:127.0.0.1',
    'NAT64'              => '64:ff9b::a00:1',
    'IPv6 documentation' => '2001:db8::1',
    '6to4 to loopback'   => '2002:7f00:1::1',
    'mapped private'     => '::ffff:10.0.0.1',
    'broadcast'          => '255.255.255.255',
    'discard only'       => '100::1',
    'not an address'     => 'example.com',
]);

it('accepts public addresses', function (string $ip) {
    expect(UrlGuard::isPublicAddress($ip))->toBeTrue();
})->with([
    'IPv4'             => '93.184.215.14',
    'IPv6'             => '2606:2800:21f:cb07:6820:80da:af6b:8b2c',
    'IPv4-mapped IPv6' => '::ffff:93.184.215.14',
]);

it('pins requests to the checked address of a public host', function () {
    $guard = new UrlGuard(static fn (string $host): array => ['93.184.215.14', '2606:2800:21f:cb07:6820:80da:af6b:8b2c']);

    $resolved = $guard->resolve('https://Hooks.Example.com:8443/chief');

    expect($resolved->curlOptions())->toBe([CURLOPT_RESOLVE => ['hooks.example.com:8443:93.184.215.14']]);
});

it('rejects a host when any of its addresses is private', function () {
    $guard = new UrlGuard(static fn (string $host): array => ['93.184.215.14', '10.0.0.5']);

    $guard->resolve('https://hooks.example.com/chief');
})->throws(BlockedUrl::class, 'private or reserved address');

it('rejects URLs it should never call', function (string $url, string $message) {
    $guard = new UrlGuard(static fn (string $host): array => $host === 'nowhere.example.com' ? [] : ['93.184.215.14']);

    expect(static fn () => $guard->resolve($url))->toThrow(BlockedUrl::class, $message);
})->with([
    'plain HTTP'      => ['http://hooks.example.com/chief', 'must use HTTPS'],
    'other scheme'    => ['ftp://hooks.example.com/chief', 'must use HTTPS'],
    'credentials'     => ['https://user:pass@hooks.example.com/chief', 'cannot contain credentials'],
    'private literal' => ['https://192.168.1.1/chief', 'private or reserved address'],
    'IPv6 literal'    => ['https://[::1]/chief', 'private or reserved address'],
    'unresolvable'    => ['https://nowhere.example.com/chief', 'does not resolve'],
    'not a URL'       => ['hooks.example.com', 'not valid'],
]);

it('allows plain HTTP and private addresses when the app allows them for local development', function () {
    config(['chief.webhooks.allow_private_addresses' => true]);

    $resolved = (new UrlGuard(static fn (string $host): array => ['127.0.0.1']))->resolve('http://localhost:8080/hooks');

    expect($resolved->curlOptions())->toBe([]);
});
