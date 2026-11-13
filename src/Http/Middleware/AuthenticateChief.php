<?php

namespace ChiefTools\SDK\Http\Middleware;

use Closure;
use RuntimeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use ChiefTools\SDK\Webhook\Outbound\Signing\Signer;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;

/**
 * Authenticates the webhooks Account Chief sends to the app.
 *
 * Deliveries are signed the Standard Webhooks way with `chief.webhook_signing_secret`, the secret of the app's endpoint
 * in Account Chief. A delivery that is not signed with it is refused.
 */
class AuthenticateChief implements AuthenticatesRequests
{
    /** How far a delivery's timestamp may be from now, so a captured delivery cannot be replayed later. */
    public const int TIMESTAMP_TOLERANCE_SECONDS = 300;

    /** The cache key prefix of deliveries that were handled, followed by their `webhook-id`. */
    private const string HANDLED_CACHE_KEY = 'chief-webhook:handled:';

    public function handle(Request $request, Closure $next): mixed
    {
        $signingSecret = (string)config('chief.webhook_signing_secret');

        if ($signingSecret === '') {
            throw new RuntimeException('Missing a Chief webhook signing secret, not accepting any webhook calls!');
        }

        abort_unless($this->hasValidSignature($request, $signingSecret), 401);

        return $this->handleOnce($request, $next);
    }

    private function hasValidSignature(Request $request, string $signingSecret): bool
    {
        $id        = $request->header('webhook-id');
        $timestamp = $request->header('webhook-timestamp');

        if (!is_string($id) || $id === '' || !is_string($timestamp) || !ctype_digit($timestamp)) {
            return false;
        }

        if (abs(now()->getTimestamp() - (int)$timestamp) > self::TIMESTAMP_TOLERANCE_SECONDS) {
            return false;
        }

        $expected = Signer::signature($id, (int)$timestamp, $request->getContent(), [$signingSecret]);

        // The sender signs with every secret that is current while it rotates, any one of them matching is enough
        foreach (explode(' ', (string)$request->header('webhook-signature')) as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Skips a delivery that was already handled within the replay window, retries of a delivery keep their `webhook-id`.
     * A delivery is only remembered once it was handled, so a failed attempt can be retried.
     */
    private function handleOnce(Request $request, Closure $next): mixed
    {
        $cacheKey = self::HANDLED_CACHE_KEY . hash('sha256', (string)$request->header('webhook-id'));

        if (Cache::has($cacheKey)) {
            return response()->json([
                'status'    => 'OK!',
                'handled'   => false,
                'duplicate' => true,
            ]);
        }

        $response = $next($request);

        if (!$response instanceof Response || $response->isSuccessful()) {
            Cache::put($cacheKey, true, self::TIMESTAMP_TOLERANCE_SECONDS * 2);
        }

        return $response;
    }
}
