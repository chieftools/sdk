<?php

namespace ChiefTools\SDK\Webhook\Outbound;

use Throwable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use ChiefTools\SDK\Webhook\Outbound\Models\Attempt;
use ChiefTools\SDK\Webhook\Outbound\Signing\Signer;
use ChiefTools\SDK\Webhook\Outbound\Models\Delivery;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;
use ChiefTools\SDK\Webhook\Outbound\Security\UrlGuard;
use ChiefTools\SDK\Webhook\Outbound\Enums\AttemptError;
use ChiefTools\SDK\Webhook\Outbound\Enums\DeliveryStatus;
use ChiefTools\SDK\Webhook\Outbound\Enums\DisabledReason;
use ChiefTools\SDK\Webhook\Outbound\Exceptions\BlockedUrl;
use ChiefTools\SDK\Webhook\Outbound\Events\EndpointFailing;
use ChiefTools\SDK\Webhook\Outbound\Actions\DisableEndpoint;
use ChiefTools\SDK\Webhook\Outbound\Events\EndpointDisabled;

/**
 * Makes an attempt of a delivery and moves the delivery and its endpoint on: done on a 2xx response, otherwise the next
 * attempt is scheduled until the schedule runs out. Endpoints that keep failing are reported and eventually disabled.
 */
class Deliverer
{
    public const int CONNECT_TIMEOUT_SECONDS = 5;

    public const int TIMEOUT_SECONDS = 15;

    /** A `Retry-After` longer than this is cut short, so a receiver cannot park its deliveries for days. */
    public const int MAX_RETRY_AFTER_SECONDS = 21600;

    /** The part of a response body that is kept for inspection. */
    public const int MAX_RESPONSE_BODY_BYTES = 16384;

    /** Responses larger than this are cut off mid-download, endpoints have no reason to send this much back. */
    private const int MAX_DOWNLOAD_BYTES = 1048576;

    /** Every attempt failing this long reports the endpoint as failing. */
    private const int FAILING_NOTIFY_AFTER_SECONDS = 3600;

    /** An endpoint is reported as failing at most this often. */
    private const int FAILING_NOTIFY_EVERY_SECONDS = 86400;

    public function __construct(
        private readonly UrlGuard $guard,
        private readonly DisableEndpoint $disableEndpoint,
    ) {}

    /**
     * @param bool $manual someone resent it: it works whatever the state of the delivery and does not use up the
     *                     automatic retries, a failure keeps the next scheduled one or fails a finished delivery
     */
    public function attempt(Delivery $delivery, bool $manual = false): ?Attempt
    {
        $delivery->loadMissing(['endpoint', 'event']);

        $endpoint = $delivery->endpoint;

        if (!$endpoint->isEnabled()) {
            $delivery->forceFill($manual
                ? ['manual_attempt_at' => null]
                : ['status' => DeliveryStatus::CANCELLED, 'next_attempt_at' => null])->save();

            return null;
        }

        $retrying = $delivery->isPending();

        $number    = $delivery->attempts + 1;
        $timestamp = now()->getTimestamp();
        $body      = $delivery->event->payload;
        $headers   = [
            'Content-Type' => 'application/json',
            'User-Agent'   => WebhookConfig::userAgent(),
            // Responses are kept for inspection only, a compressed one would need unpacking first
            'Accept-Encoding' => 'identity',
            ...Signer::headers($delivery->event->formatted_id, $timestamp, $body, $endpoint->signingSecrets()),
        ];

        // When the request was sent, which orders attempts of deliveries that run at the same time
        $sentAt = now();
        $result = $this->send($endpoint, $headers, $body);

        $attempt = Attempt::query()->create([
            'delivery_id'     => $delivery->getKey(),
            'endpoint_id'     => $endpoint->getKey(),
            'number'          => $number,
            'manual'          => $manual,
            'request_headers' => $headers,
            'attempted_at'    => $sentAt,
            ...$result,
        ]);

        $delivery->forceFill([
            'attempts'             => $number,
            'last_attempted_at'    => $attempt->attempted_at,
            'last_response_status' => $attempt->response_status,
            ...($manual ? ['manual_attempt_at' => null] : []),
        ]);

        if ($attempt->succeeded) {
            $delivery->forceFill(['status' => DeliveryStatus::SUCCEEDED, 'next_attempt_at' => null])->save();

            $this->recordSuccess($endpoint, $attempt);

            return $attempt;
        }

        // A receiver that asks us to slow down is busy, not broken: it does not count towards warning or disabling it
        $throttled = $attempt->response_status === 429;

        if ($manual) {
            // A failed resend leaves scheduled retries alone, a finished delivery ends up failed
            if (!$retrying) {
                $delivery->forceFill(['status' => DeliveryStatus::FAILED, 'next_attempt_at' => null]);
            }

            $delivery->save();

            if (!$throttled) {
                $this->recordFailure($endpoint, $attempt, exhausted: false);
            }

            return $attempt;
        }

        $automaticAttempts = $number - $delivery->attemptLog()->where('manual', true)->count();
        $wait              = WebhookConfig::retrySchedule()[$automaticAttempts - 1] ?? null;
        $retryAfter        = self::retryAfter($attempt);

        // The receiver's own `Retry-After` wins when it asks for longer than the schedule
        if ($wait !== null && $retryAfter !== null) {
            $wait = max($wait, $retryAfter);
        }

        $delivery->forceFill($wait === null
            ? ['status' => DeliveryStatus::FAILED, 'next_attempt_at' => null]
            : ['next_attempt_at' => now()->addSeconds($wait)])->save();

        if (!$throttled) {
            $this->recordFailure($endpoint, $attempt, exhausted: $wait === null);
        }

        return $attempt;
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array{succeeded: bool, response_status: int|null, response_headers: array<string, string>|null, response_body: string|null, response_truncated: bool, duration_ms: int|null, error: \ChiefTools\SDK\Webhook\Outbound\Enums\AttemptError|null, error_message: string|null}
     */
    private function send(Endpoint $endpoint, array $headers, string $body): array
    {
        $result = [
            'succeeded'          => false,
            'response_status'    => null,
            'response_headers'   => null,
            'response_body'      => null,
            'response_truncated' => false,
            'duration_ms'        => null,
            'error'              => null,
            'error_message'      => null,
        ];

        try {
            $resolved = $this->guard->resolve($endpoint->url);
        } catch (BlockedUrl $e) {
            return [...$result, 'error' => AttemptError::BLOCKED, 'error_message' => $e->getMessage()];
        }

        $started = hrtime(true);

        try {
            $response = Http::withHeaders($headers)
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->timeout(self::TIMEOUT_SECONDS)
                ->withoutRedirecting()
                ->withOptions([
                    'curl'     => $resolved->curlOptions(),
                    'progress' => self::abortOversizedDownload(...),
                    // Unpacking happens below within a limit, Guzzle would unpack a small download into any size
                    'decode_content' => false,
                ])
                ->withBody($body, 'application/json')
                ->post($endpoint->url);
        } catch (ConnectionException $e) {
            $message = $e->getMessage();

            return [
                ...$result,
                'duration_ms'   => self::elapsedMilliseconds($started),
                'error'         => str_contains($message, 'cURL error 28') || str_contains(strtolower($message), 'timed out') ? AttemptError::TIMEOUT : AttemptError::CONNECTION,
                'error_message' => Str::limit(trim(preg_replace('/ \(see https?:\/\/\S+\).*$/', '', $message) ?? $message), 250),
            ];
        } catch (Throwable $e) {
            // Our own failure to send, like a request option the HTTP client rejects: the attempt fails like any
            // other so the delivery keeps its schedule, and we hear about it
            report($e);

            return [
                ...$result,
                'duration_ms'   => self::elapsedMilliseconds($started),
                'error'         => AttemptError::CONNECTION,
                'error_message' => 'The request could not be sent.',
            ];
        }

        [$responseBody, $truncated] = self::readableBody($response->body(), (string)$response->header('Content-Encoding'));

        return [
            'succeeded'          => $response->successful(),
            'response_status'    => $response->status(),
            'response_headers'   => self::flattenHeaders($response),
            'response_body'      => $responseBody === null ? null : mb_scrub(mb_strcut($responseBody, 0, self::MAX_RESPONSE_BODY_BYTES), 'UTF-8'),
            'response_truncated' => $truncated,
            'duration_ms'        => self::elapsedMilliseconds($started),
            'error'              => $response->redirect() ? AttemptError::REDIRECT : null,
            'error_message'      => $response->redirect() ? Str::limit('Redirected to ' . $response->header('Location'), 250) : null,
        ];
    }

    /**
     * The seconds a `429` or `503` response asked to wait in its `Retry-After`, as seconds or an HTTP date, at most
     * `MAX_RETRY_AFTER_SECONDS`. Null without a usable one.
     */
    private static function retryAfter(Attempt $attempt): ?int
    {
        if (!in_array($attempt->response_status, [429, 503], true)) {
            return null;
        }

        $value = collect($attempt->response_headers ?? [])->first(static fn (string $value, string $name): bool => strcasecmp($name, 'Retry-After') === 0);

        if ($value === null || trim($value) === '') {
            return null;
        }

        $value   = trim($value);
        $seconds = ctype_digit($value) ? (int)$value : (($time = strtotime($value)) === false ? null : $time - now()->getTimestamp());

        return $seconds === null ? null : max(1, min($seconds, self::MAX_RETRY_AFTER_SECONDS));
    }

    /**
     * Ends the endpoint's failing streak, in one statement so it needs no lock. A slow success that was sent before the
     * failure that started the streak leaves it, the endpoint failed after that request went out.
     */
    private function recordSuccess(Endpoint $endpoint, Attempt $attempt): void
    {
        Endpoint::query()
            ->whereKey($endpoint->getKey())
            ->where('failing_since', '<=', $attempt->attempted_at->format(Attempt::DATE_FORMAT))
            ->update(['failing_since' => null]);
    }

    /**
     * Adds a failure to the endpoint's streak, then warns or disables it. Deliveries of one endpoint run at the same
     * time, so this reads the endpoint again under a lock instead of trusting the copy loaded before the request.
     */
    private function recordFailure(Endpoint $endpoint, Attempt $attempt, bool $exhausted): void
    {
        /** @var array{0: class-string<\ChiefTools\SDK\Webhook\Outbound\Events\EndpointFailing|\ChiefTools\SDK\Webhook\Outbound\Events\EndpointDisabled>, 1: \ChiefTools\SDK\Webhook\Outbound\Models\Endpoint}|null $notify */
        $notify = DB::transaction(function () use ($endpoint, $attempt, $exhausted): ?array {
            $endpoint = Endpoint::query()->lockForUpdate()->find($endpoint->getKey());

            if ($endpoint === null || !$endpoint->isEnabled()) {
                return null;
            }

            // A request sent later already succeeded, so the endpoint works and this failure is old news
            $superseded = Attempt::query()
                ->where('endpoint_id', $endpoint->getKey())
                ->where('succeeded', true)
                ->where('attempted_at', '>', $attempt->attempted_at->format(Attempt::DATE_FORMAT))
                ->exists();

            if ($superseded) {
                return null;
            }

            $now = now();

            $endpoint->failing_since ??= $attempt->attempted_at;

            if ($endpoint->failing_since->lte($now->copy()->subDays(WebhookConfig::disableAfterDays()))) {
                $this->disableEndpoint->handle($endpoint, DisabledReason::FAILING);

                return [EndpointDisabled::class, $endpoint];
            }

            $failingLongEnough = $endpoint->failing_since->lte($now->copy()->subSeconds(self::FAILING_NOTIFY_AFTER_SECONDS));
            $notifiedRecently  = $endpoint->failure_notified_at?->gt($now->copy()->subSeconds(self::FAILING_NOTIFY_EVERY_SECONDS)) ?? false;

            if (($exhausted || $failingLongEnough) && !$notifiedRecently) {
                $endpoint->failure_notified_at = $now;
                $endpoint->save();

                return [EndpointFailing::class, $endpoint];
            }

            if ($endpoint->isDirty()) {
                $endpoint->save();
            }

            return null;
        });

        if ($notify !== null) {
            [$event, $endpoint] = $notify;

            $event::dispatch($endpoint);
        }
    }

    /**
     * Guzzle's progress callback, aborting the download of a response larger than endpoints have any reason to send:
     * as soon as its declared size is too large, or once that much arrived without one. Aborting from it needs Guzzle 8.
     */
    private static function abortOversizedDownload(int $downloadTotal, int $downloaded): bool
    {
        return max($downloadTotal, $downloaded) > self::MAX_DOWNLOAD_BYTES;
    }

    /**
     * The start of the response body to keep, unpacked from gzip or deflate without ever holding more than that. Bodies
     * in other encodings, like Brotli, are not kept.
     *
     * @return array{0: string|null, 1: bool} the body, and whether it was longer than what is kept
     */
    private static function readableBody(string $body, string $encoding): array
    {
        $encoding = strtolower(trim($encoding));

        if ($encoding === '' || $encoding === 'identity') {
            return [$body, strlen($body) > self::MAX_RESPONSE_BODY_BYTES];
        }

        if (!in_array($encoding, ['gzip', 'x-gzip', 'deflate'], true)) {
            return [null, false];
        }

        // Deflate should be zlib wrapped, but some servers send it raw, each format refuses the others right away
        foreach ([ZLIB_ENCODING_GZIP, ZLIB_ENCODING_DEFLATE, ZLIB_ENCODING_RAW] as $format) {
            $unpacked = self::inflateStart($body, $format);

            if ($unpacked !== null) {
                return $unpacked;
            }
        }

        return [null, false];
    }

    /**
     * Unpacks a compressed body a small piece at a time and stops once the kept part is complete, a few hundred bytes
     * can unpack into megabytes.
     *
     * @return array{0: string, 1: bool}|null the unpacked start, and whether there was more, or null when it is not in this format
     */
    private static function inflateStart(string $compressed, int $format): ?array
    {
        $context = inflate_init($format);
        $output  = '';

        if ($context === false) {
            return null;
        }

        foreach (str_split($compressed, 256) as $piece) {
            $unpacked = @inflate_add($context, $piece, ZLIB_SYNC_FLUSH);

            if ($unpacked === false) {
                return null;
            }

            $output .= $unpacked;

            if (strlen($output) > self::MAX_RESPONSE_BODY_BYTES) {
                return [substr($output, 0, self::MAX_RESPONSE_BODY_BYTES), true];
            }
        }

        return [$output, false];
    }

    /** @return array<string, string> */
    private static function flattenHeaders(Response $response): array
    {
        return collect($response->headers())
            ->map(static fn (array|string $values): string => Str::limit(is_array($values) ? implode(', ', $values) : $values, 1000))
            ->take(64)
            ->all();
    }

    private static function elapsedMilliseconds(int $started): int
    {
        return (int)round((hrtime(true) - $started) / 1_000_000);
    }
}
