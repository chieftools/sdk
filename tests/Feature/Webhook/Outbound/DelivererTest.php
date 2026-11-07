<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use ChiefTools\SDK\Webhook\Outbound\Webhooks;
use Tests\Fixtures\Webhooks\ExampleEventType;
use ChiefTools\SDK\Webhook\Outbound\Deliverer;
use ChiefTools\SDK\Webhook\Outbound\Models\Delivery;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;
use ChiefTools\SDK\Webhook\Outbound\Jobs\SendDelivery;
use ChiefTools\SDK\Webhook\Outbound\Enums\AttemptError;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use ChiefTools\SDK\Webhook\Outbound\Enums\DeliveryStatus;
use ChiefTools\SDK\Webhook\Outbound\Enums\DisabledReason;
use ChiefTools\SDK\Webhook\Outbound\Enums\EndpointStatus;
use ChiefTools\SDK\Webhook\Outbound\Events\EndpointFailing;
use ChiefTools\SDK\Webhook\Outbound\Events\EndpointDisabled;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();
    resolveWebhookHostsTo();
});

/** A pending delivery of a `lantern.lit` event to a new endpoint, without running its queued attempt. */
function pendingDelivery(array $endpointAttributes = []): Delivery
{
    Queue::fake([SendDelivery::class]);

    $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'), $endpointAttributes);

    app(Webhooks::class)->dispatch($endpoint->team_id, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);

    return Delivery::query()->sole();
}

it('sends the signed event and marks the delivery as succeeded on a 2xx response', function () {
    $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00'));
    $delivery = pendingDelivery();
    Http::fake(['hooks.example.com/chief' => Http::response(['ok' => true], 202, ['X-Request-Id' => 'abc'])]);

    $attempt = app(Deliverer::class)->attempt($delivery);

    $payload = $delivery->event->payload;
    $id      = 'event_' . $delivery->event_id;
    Http::assertSent(fn (Request $request) => $request->url() === 'https://hooks.example.com/chief'
        && $request->method() === 'POST'
        && $request->body() === $payload
        && $request->header('webhook-id')[0] === $id
        && $request->header('webhook-timestamp')[0] === (string)now()->getTimestamp()
        && $request->header('webhook-signature')[0] === 'v1,' . base64_encode(hash_hmac('sha256', "{$id}." . now()->getTimestamp() . ".{$payload}", base64_decode('MfKQ9r8GKYqrTwjUPD8ILPZIo2LaLaSw'), true)));
    expect($attempt)->number->toBe(1)
        ->succeeded->toBeTrue()
        ->response_status->toBe(202)
        ->response_body->toBe('{"ok":true}')
        ->response_headers->toHaveKey('X-Request-Id', 'abc')
        ->error->toBeNull();
    expect($delivery->refresh())->status->toBe(DeliveryStatus::SUCCEEDED)
        ->attempts->toBe(1)
        ->next_attempt_at->toBeNull()
        ->last_response_status->toBe(202);
});

it('schedules the next attempt from the retry schedule after a failed response', function () {
    $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00'));
    $delivery = pendingDelivery();
    Http::fake(['hooks.example.com/chief' => Http::response('Bad gateway', 502)]);

    app(Deliverer::class)->attempt($delivery);

    expect($delivery->refresh())->status->toBe(DeliveryStatus::PENDING)
        ->attempts->toBe(1)
        ->next_attempt_at->toEqual(now()->addMinute());
    expect($delivery->endpoint->refresh()->failing_since)->toEqual(now());
});

it('fails the delivery and reports the endpoint after the last attempt', function () {
    $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00'));
    Event::fake([EndpointFailing::class]);
    $delivery = pendingDelivery();
    $delivery->forceFill(['attempts' => 8])->save();
    Http::fake(['hooks.example.com/chief' => Http::response('Nope', 500)]);

    app(Deliverer::class)->attempt($delivery);

    expect($delivery->refresh())->status->toBe(DeliveryStatus::FAILED)
        ->attempts->toBe(9)
        ->next_attempt_at->toBeNull();
    Event::assertDispatched(EndpointFailing::class, fn (EndpointFailing $event) => $event->endpoint->is($delivery->endpoint));
});

it('reports an endpoint that has been failing for an hour at most once a day', function () {
    $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00'));
    Event::fake([EndpointFailing::class]);
    $delivery = pendingDelivery(['failing_since' => now()->subMinutes(61)]);
    Http::fake(['hooks.example.com/chief' => Http::response('Nope', 500)]);

    app(Deliverer::class)->attempt($delivery);
    $delivery->refresh()->forceFill(['next_attempt_at' => now()])->save();
    app(Deliverer::class)->attempt($delivery);

    Event::assertDispatchedTimes(EndpointFailing::class, 1);
    expect($delivery->endpoint->refresh()->failure_notified_at)->toEqual(now());
});

it('disables an endpoint that only failed for days and cancels its other deliveries', function () {
    $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00'));
    Event::fake([EndpointDisabled::class, EndpointFailing::class]);
    $delivery = pendingDelivery(['failing_since' => now()->subDays(5)]);
    app(Webhooks::class)->dispatch($delivery->team_id, ExampleEventType::LANTERN_DIMMED, ['id' => 'lan_1']);
    $other    = Delivery::query()->whereKeyNot($delivery->id)->sole();
    Http::fake(['hooks.example.com/chief' => Http::response('Nope', 500)]);

    app(Deliverer::class)->attempt($delivery);

    expect($delivery->endpoint->refresh())->status->toBe(EndpointStatus::DISABLED)
        ->disabled_reason->toBe(DisabledReason::FAILING);
    expect($delivery->refresh()->status)->toBe(DeliveryStatus::CANCELLED);
    expect($other->refresh()->status)->toBe(DeliveryStatus::CANCELLED);
    Event::assertDispatched(EndpointDisabled::class);
    Event::assertNotDispatched(EndpointFailing::class);
});

it('clears the failure record of an endpoint once a delivery succeeds', function () {
    $delivery = pendingDelivery(['failing_since' => now()->subHours(3)]);
    Http::fake(['hooks.example.com/chief' => Http::response()]);

    app(Deliverer::class)->attempt($delivery);

    expect($delivery->endpoint->refresh()->failing_since)->toBeNull();
});

it('records why an attempt without a usable response failed', function (Closure $response, AttemptError $error, string $message) {
    $delivery = pendingDelivery();
    Http::fake(['hooks.example.com/chief' => $response]);

    $attempt = app(Deliverer::class)->attempt($delivery);

    expect($attempt)->succeeded->toBeFalse()
        ->error->toBe($error)
        ->error_message->toContain($message);
    expect($delivery->refresh()->status)->toBe(DeliveryStatus::PENDING);
})->with([
    'timeout'    => [fn () => fn () => throw new Illuminate\Http\Client\ConnectionException('cURL error 28: Operation timed out after 15001 milliseconds (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for https://hooks.example.com/chief'), AttemptError::TIMEOUT, 'Operation timed out after 15001 milliseconds'],
    'connection' => [fn () => fn () => throw new Illuminate\Http\Client\ConnectionException('cURL error 7: Failed to connect to hooks.example.com port 443'), AttemptError::CONNECTION, 'Failed to connect'],
    'redirect'   => [fn () => fn () => Http::response('', 301, ['Location' => 'https://elsewhere.example.com']), AttemptError::REDIRECT, 'Redirected to https://elsewhere.example.com'],
]);

it('blocks an endpoint whose host now resolves to a private address without sending', function () {
    $delivery = pendingDelivery();
    resolveWebhookHostsTo('10.0.0.8');
    Http::fake(['hooks.example.com/chief' => Http::response()]);

    $attempt = app(Deliverer::class)->attempt($delivery);

    expect($attempt)->error->toBe(AttemptError::BLOCKED)
        ->response_status->toBeNull();
    Http::assertNothingSent();
});

it('keeps only the start of a large response body', function () {
    $delivery = pendingDelivery();
    Http::fake(['hooks.example.com/chief' => Http::response(str_repeat('a', Deliverer::MAX_RESPONSE_BODY_BYTES + 10))]);

    $attempt = app(Deliverer::class)->attempt($delivery);

    expect($attempt)->response_truncated->toBeTrue()
        ->and(strlen($attempt->response_body))->toBe(Deliverer::MAX_RESPONSE_BODY_BYTES);
});

it('signs with the previous secret too while it has not expired', function () {
    $delivery = pendingDelivery([
        'previous_secret'            => 'whsec_' . base64_encode('previous-signing-secret!'),
        'previous_secret_expires_at' => now()->addHour(),
    ]);
    Http::fake(['hooks.example.com/chief' => Http::response()]);

    app(Deliverer::class)->attempt($delivery);

    Http::assertSent(fn (Request $request) => count(explode(' ', $request->header('webhook-signature')[0])) === 2);
});

it('cancels the delivery without sending when the endpoint was disabled meanwhile', function () {
    $delivery = pendingDelivery();
    Endpoint::query()->whereKey($delivery->endpoint_id)->update(['status' => EndpointStatus::DISABLED]);
    Http::fake(['hooks.example.com/chief' => Http::response()]);

    $attempt = app(Deliverer::class)->attempt($delivery);

    expect($attempt)->toBeNull()
        ->and($delivery->refresh()->status)->toBe(DeliveryStatus::CANCELLED);
    Http::assertNothingSent();
});

it('sends through the real HTTP client with the options it accepts', function () {
    config(['chief.webhooks.allow_private_addresses' => true]);
    Http::allowStrayRequests();
    // A port nothing listens on, so curl applies every request option and then fails to connect
    $delivery = pendingDelivery(['url' => 'http://127.0.0.1:9/chief']);

    $attempt = app(Deliverer::class)->attempt($delivery);

    expect($attempt)->error->toBe(AttemptError::CONNECTION)
        ->error_message->not->toBe('The request could not be sent.')
        ->error_message->toContain('127.0.0.1');
});

it('records an attempt that could not be sent and reports why', function () {
    Illuminate\Support\Facades\Exceptions::fake();
    $delivery = pendingDelivery();
    Http::fake(['hooks.example.com/chief' => fn () => throw new InvalidArgumentException('Unsupported request option.')]);

    $attempt = app(Deliverer::class)->attempt($delivery);

    expect($attempt)->error->toBe(AttemptError::CONNECTION)
        ->error_message->toBe('The request could not be sent.')
        ->and($delivery->refresh()->status)->toBe(DeliveryStatus::PENDING);
    Illuminate\Support\Facades\Exceptions::assertReported(InvalidArgumentException::class);
});

describe('manual attempts', function () {
    it('keeps the next scheduled retry when a resend of a retrying delivery fails', function () {
        $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00'));
        $delivery = pendingDelivery();
        Http::fake(['hooks.example.com/chief' => Http::response('Nope', 500)]);
        app(Deliverer::class)->attempt($delivery);

        $attempt = app(Deliverer::class)->attempt($delivery->refresh(), manual: true);

        expect($attempt)->number->toBe(2)->manual->toBeTrue();
        expect($delivery->refresh())->status->toBe(DeliveryStatus::PENDING)
            ->attempts->toBe(2)
            ->next_attempt_at->format('Y-m-d H:i:s')->toBe('2026-11-07 12:01:00');
    });

    it('does not count resends towards the automatic retries', function () {
        $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00'));
        $delivery = pendingDelivery();
        Http::fake(['hooks.example.com/chief' => Http::response('Nope', 500)]);
        app(Deliverer::class)->attempt($delivery);
        app(Deliverer::class)->attempt($delivery->refresh(), manual: true);

        app(Deliverer::class)->attempt($delivery->refresh());

        // The second automatic attempt waits the schedule's second step, five minutes
        expect($delivery->refresh())->attempts->toBe(3)
            ->next_attempt_at->format('Y-m-d H:i:s')->toBe('2026-11-07 12:05:00');
    });

    it('sets a finished delivery to the result of its resend', function (DeliveryStatus $before, int $status, DeliveryStatus $after) {
        $delivery = pendingDelivery();
        $delivery->forceFill(['status' => $before, 'next_attempt_at' => null])->save();
        Http::fake(['hooks.example.com/chief' => Http::response('', $status)]);

        app(Deliverer::class)->attempt($delivery, manual: true);

        expect($delivery->refresh())->status->toBe($after)
            ->next_attempt_at->toBeNull();
    })->with([
        'failed, then succeeded'   => [DeliveryStatus::FAILED, 200, DeliveryStatus::SUCCEEDED],
        'succeeded, then failed'   => [DeliveryStatus::SUCCEEDED, 500, DeliveryStatus::FAILED],
        'cancelled, then received' => [DeliveryStatus::CANCELLED, 204, DeliveryStatus::SUCCEEDED],
    ]);

    it('does not report the endpoint as failing for a resend that fails', function () {
        Event::fake([EndpointFailing::class]);
        $delivery = pendingDelivery();
        $delivery->forceFill(['status' => DeliveryStatus::FAILED, 'attempts' => 9, 'next_attempt_at' => null])->save();
        Http::fake(['hooks.example.com/chief' => Http::response('Nope', 500)]);

        app(Deliverer::class)->attempt($delivery, manual: true);

        Event::assertNotDispatched(EndpointFailing::class);
    });
});

describe('endpoint health with deliveries at the same time', function () {
    /** Two pending deliveries to the same endpoint, each with its endpoint loaded now. */
    function twoPendingDeliveries(): array
    {
        $first  = pendingDelivery();
        app(Webhooks::class)->dispatch($first->team_id, ExampleEventType::LANTERN_DIMMED, ['id' => 'lan_1']);
        $second = Delivery::query()->whereKeyNot($first->id)->sole();

        return [$first->load(['endpoint', 'event']), $second->load(['endpoint', 'event'])];
    }

    it('ends the failing streak on a success whose copy of the endpoint was loaded before the failure', function () {
        $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00'));
        [$failing, $succeeding] = twoPendingDeliveries();
        Http::fake(['hooks.example.com/chief' => Http::sequence()->push('Nope', 500)->push()]);

        app(Deliverer::class)->attempt($failing);
        $this->travel(1)->minute();
        app(Deliverer::class)->attempt($succeeding);

        expect($failing->endpoint->refresh()->failing_since)->toBeNull();
    });

    it('keeps a streak that started after a slow success was sent', function () {
        $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00'));
        [$succeeding] = twoPendingDeliveries();
        // A failure sent at 12:01 was recorded while the success sent at 12:00 was still waiting for its response
        Endpoint::query()->whereKey($succeeding->endpoint_id)->update(['failing_since' => '2026-11-07 12:01:00']);
        Http::fake(['hooks.example.com/chief' => Http::response()]);

        app(Deliverer::class)->attempt($succeeding);

        expect($succeeding->endpoint->refresh()->failing_since->format('H:i'))->toBe('12:01');
    });

    it('orders requests sent within the same second', function () {
        Event::fake([EndpointFailing::class]);
        $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00.600'));
        [$failing, $succeeding] = twoPendingDeliveries();
        Http::fake(['hooks.example.com/chief' => Http::sequence()->push()->push('Nope', 500)]);
        app(Deliverer::class)->attempt($succeeding);

        // The failure's request went out 400 ms before the success, and its response arrived after it
        $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00.200'));
        app(Deliverer::class)->attempt($failing);

        expect($failing->endpoint->refresh()->failing_since)->toBeNull()
            ->and($failing->attemptLog()->sole()->attempted_at->format('H:i:s.v'))->toBe('12:00:00.200');
    });

    it('ignores a failure when a request sent after it already succeeded', function () {
        Event::fake([EndpointFailing::class]);
        $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:05:00'));
        [$failing, $succeeding] = twoPendingDeliveries();
        Http::fake(['hooks.example.com/chief' => Http::sequence()->push()->push('Nope', 500)]);
        app(Deliverer::class)->attempt($succeeding);

        // The failure's request went out at 12:00, before the success
        $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00'));
        $failing->forceFill(['attempts' => 8])->save();
        app(Deliverer::class)->attempt($failing);

        expect($failing->endpoint->refresh()->failing_since)->toBeNull()
            ->and($failing->refresh()->status)->toBe(DeliveryStatus::FAILED);
        Event::assertNotDispatched(EndpointFailing::class);
    });
});

describe('compressed responses', function () {
    it('asks for responses without compression', function () {
        $delivery = pendingDelivery();
        Http::fake(['hooks.example.com/chief' => Http::response()]);

        app(Deliverer::class)->attempt($delivery);

        Http::assertSent(fn (Request $request) => $request->header('Accept-Encoding') === ['identity']);
    });

    it('keeps the start of a compressed body without unpacking all of it', function (string $encoding, string $compress) {
        $delivery = pendingDelivery();
        Http::fake(['hooks.example.com/chief' => Http::response($compress(str_repeat('a', 8 * 1024 * 1024)), 200, ['Content-Encoding' => $encoding])]);

        $attempt = app(Deliverer::class)->attempt($delivery);

        expect($attempt)->response_truncated->toBeTrue()
            ->and($attempt->response_body)->toBe(str_repeat('a', Deliverer::MAX_RESPONSE_BODY_BYTES));
    })->with([
        'gzip'         => ['gzip', 'gzencode'],
        'zlib deflate' => ['deflate', 'gzcompress'],
        'raw deflate'  => ['deflate', 'gzdeflate'],
    ]);

    it('keeps no body in an encoding it does not unpack', function () {
        $delivery = pendingDelivery();
        Http::fake(['hooks.example.com/chief' => Http::response('compressed bytes', 200, ['Content-Encoding' => 'br'])]);

        $attempt = app(Deliverer::class)->attempt($delivery);

        expect($attempt)->succeeded->toBeTrue()
            ->response_body->toBeNull();
    });

    it('never holds a whole compressed response in memory from a real receiver', function () {
        config(['chief.webhooks.allow_private_addresses' => true]);
        Http::allowStrayRequests();
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $port   = (int)substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);
        $server = new Symfony\Component\Process\Process([PHP_BINARY, '-S', "127.0.0.1:{$port}", dirname(__DIR__, 3) . '/Fixtures/Webhooks/compressed-response.php']);
        $server->start();

        try {
            retry(50, static function () use ($port): void {
                $connection = @fsockopen('127.0.0.1', $port, timeout: 0.1);

                throw_if($connection === false, RuntimeException::class, 'The receiver is not listening yet.');

                fclose($connection);
            }, 100);

            $delivery = pendingDelivery(['url' => "http://127.0.0.1:{$port}/hooks"]);
            memory_reset_peak_usage();
            $before   = memory_get_peak_usage();

            $attempt = app(Deliverer::class)->attempt($delivery);

            expect($attempt)->succeeded->toBeTrue()
                ->response_truncated->toBeTrue()
                ->and(strlen($attempt->response_body))->toBe(Deliverer::MAX_RESPONSE_BODY_BYTES)
                ->and(memory_get_peak_usage() - $before)->toBeLessThan(8 * 1024 * 1024);
        } finally {
            $server->stop();
        }
    });
});

describe('Retry-After', function () {
    it('waits as long as a throttling receiver asks without counting it as failing', function () {
        $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00'));
        Event::fake([EndpointFailing::class]);
        $delivery = pendingDelivery();
        $delivery->forceFill(['attempts' => 8])->save();
        Http::fake(['hooks.example.com/chief' => Http::response('Slow down', 429, ['Retry-After' => '120'])]);

        app(Deliverer::class)->attempt($delivery);

        expect($delivery->refresh())->status->toBe(DeliveryStatus::FAILED)
            ->and($delivery->endpoint->refresh()->failing_since)->toBeNull();
        Event::assertNotDispatched(EndpointFailing::class);
    });

    it('takes the longer of the schedule and the receiver\'s wait', function (int $status, string $retryAfter, string $next) {
        $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00'));
        $delivery = pendingDelivery();
        Http::fake(['hooks.example.com/chief' => Http::response('', $status, ['Retry-After' => $retryAfter])]);

        app(Deliverer::class)->attempt($delivery);

        expect($delivery->refresh()->next_attempt_at->format('Y-m-d H:i:s'))->toBe($next);
    })->with([
        'seconds'           => [429, '120', '2026-11-07 12:02:00'],
        'HTTP date'         => [503, 'Sat, 07 Nov 2026 14:00:00 GMT', '2026-11-07 14:00:00'],
        'shorter than wait' => [429, '5', '2026-11-07 12:01:00'],
        'capped'            => [429, '604800', '2026-11-07 18:00:00'],
        'ignored on 500'    => [500, '600', '2026-11-07 12:01:00'],
    ]);
});
