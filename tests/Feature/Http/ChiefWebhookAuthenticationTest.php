<?php

use ChiefTools\SDK\Chief;
use Illuminate\Testing\TestResponse;
use Illuminate\Support\Facades\Cache;
use ChiefTools\SDK\Webhook\WebhookEvent;
use ChiefTools\SDK\Webhook\Handlers\Handler;
use ChiefTools\SDK\Webhook\Outbound\Signing\Signer;

const CHIEF_WEBHOOK_TEST_SIGNING_SECRET = 'whsec_c3ludGhldGljLXNpZ25pbmctc2VjcmV0LTI0Yg==';

beforeEach(function () {
    config([
        'chief.webhook_signing_secret'  => CHIEF_WEBHOOK_TEST_SIGNING_SECRET,
        'services.chief.webhook_secret' => 'synthetic-legacy-secret',
    ]);
});

/** @param array<string, string> $headers */
function chiefWebhookRequest(string $body, array $headers): TestResponse
{
    $server = ['CONTENT_TYPE' => 'application/json'];

    foreach ($headers as $name => $value) {
        $server['HTTP_' . strtoupper(str_replace('-', '_', $name))] = $value;
    }

    return test()->call('POST', route('chief.webhook'), server: $server, content: $body);
}

function chiefWebhookBody(string $cacheKey = 'synthetic-token-cache'): string
{
    return json_encode(['event' => enum_value(WebhookEvent::TOKEN_DESTROYED), 'user' => null, 'data' => ['cache_key' => $cacheKey]], JSON_THROW_ON_ERROR);
}

/** @return array{webhook-id: string, webhook-timestamp: string, webhook-signature: string} */
function chiefWebhookSignatureHeaders(string $body, string $secret = CHIEF_WEBHOOK_TEST_SIGNING_SECRET, string $id = 'msg_synthetic', ?int $timestamp = null): array
{
    return Signer::headers($id, $timestamp ?? now()->getTimestamp(), $body, [$secret]);
}

it('handles a delivery signed with the signing secret', function () {
    Cache::put('synthetic-token-cache', 'cached');
    $body = chiefWebhookBody();

    chiefWebhookRequest($body, chiefWebhookSignatureHeaders($body))->assertOk()->assertJsonPath('handled', true);

    expect(Cache::has('synthetic-token-cache'))->toBeFalse();
});

it('accepts a delivery signed with any of the secrets of a rotation', function () {
    $body    = chiefWebhookBody();
    $headers = Signer::headers('msg_rotation', now()->getTimestamp(), $body, ['whsec_' . base64_encode(random_bytes(24)), CHIEF_WEBHOOK_TEST_SIGNING_SECRET]);

    chiefWebhookRequest($body, $headers)->assertOk();
});

it('refuses a signed delivery that was tampered with, expired or signed with another secret', function (Closure $headers) {
    $body = chiefWebhookBody();

    chiefWebhookRequest($body, $headers($body) + ['X-Chief-Secret' => 'synthetic-legacy-secret'])->assertUnauthorized();
})->with([
    'changed body'        => fn (string $body) => chiefWebhookSignatureHeaders(chiefWebhookBody('another-cache-key')),
    'old timestamp'       => fn (string $body) => chiefWebhookSignatureHeaders($body, timestamp: now()->subMinutes(6)->getTimestamp()),
    'future timestamp'    => fn (string $body) => chiefWebhookSignatureHeaders($body, timestamp: now()->addMinutes(6)->getTimestamp()),
    'other secret'        => fn (string $body) => chiefWebhookSignatureHeaders($body, 'whsec_' . base64_encode(random_bytes(24))),
    'missing id'          => fn (string $body) => ['webhook-id' => ''] + chiefWebhookSignatureHeaders($body),
    'malformed timestamp' => fn (string $body) => ['webhook-timestamp' => 'soon'] + chiefWebhookSignatureHeaders($body),
]);

it('handles a delivery only once within the replay window', function () {
    $body    = chiefWebhookBody();
    $headers = chiefWebhookSignatureHeaders($body);

    chiefWebhookRequest($body, $headers)->assertOk()->assertJsonPath('handled', true);

    Cache::put('synthetic-token-cache', 'cached again');

    chiefWebhookRequest($body, $headers)->assertOk()->assertJsonPath('duplicate', true);

    expect(Cache::get('synthetic-token-cache'))->toBe('cached again');
});

it('handles a retry of a delivery whose earlier attempt failed', function () {
    FailingOnceWebhookHandler::$calls = 0;
    Chief::registerWebhookHandler(WebhookEvent::CHECKOUT_SESSION_COMPLETED, FailingOnceWebhookHandler::class);
    $body    = json_encode(['event' => enum_value(WebhookEvent::CHECKOUT_SESSION_COMPLETED), 'data' => []], JSON_THROW_ON_ERROR);
    $headers = chiefWebhookSignatureHeaders($body);

    chiefWebhookRequest($body, $headers)->assertServiceUnavailable();
    chiefWebhookRequest($body, $headers)->assertOk()->assertJsonPath('status', 'handled');

    expect(FailingOnceWebhookHandler::$calls)->toBe(2);
});

it('accepts the legacy secret header while it is allowed', function () {
    chiefWebhookRequest(chiefWebhookBody(), ['X-Chief-Secret' => 'synthetic-legacy-secret'])->assertOk();
    chiefWebhookRequest(chiefWebhookBody(), ['X-Chief-Secret' => 'wrong-secret'])->assertUnauthorized();
});

it('refuses the legacy secret header once it is turned off', function () {
    config(['chief.webhook_accept_legacy_secret' => false]);

    chiefWebhookRequest(chiefWebhookBody(), ['X-Chief-Secret' => 'synthetic-legacy-secret'])->assertUnauthorized();
});

it('accepts signed deliveries on apps without a legacy secret', function () {
    config(['services.chief.webhook_secret' => null]);
    $body = chiefWebhookBody();

    chiefWebhookRequest($body, chiefWebhookSignatureHeaders($body))->assertOk();
});

final class FailingOnceWebhookHandler implements Handler
{
    public static int $calls = 0;

    public function __invoke(array $payload): ?array
    {
        abort_if(self::$calls++ === 0, 503);

        return ['status' => 'handled'];
    }
}
