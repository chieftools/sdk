<?php

namespace ChiefTools\SDK\Webhook\Outbound\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use ChiefTools\SDK\Webhook\Outbound\Signing\Signer;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;

/**
 * Gives an endpoint a new signing secret. Deliveries are signed with both secrets until the old one expires, so the
 * receiver can switch over without rejecting any.
 *
 * An endpoint keeps one previous secret. Rotating again while it still signs replaces it right away, so receivers
 * that still use it start rejecting deliveries. That needs `$replacePrevious`, after warning whoever asked.
 */
class RotateSecret
{
    public const int DEFAULT_OVERLAP_HOURS = 24;

    /**
     * @param int  $overlapHours    how long the old secret keeps signing, 0 to stop using it right away
     * @param bool $replacePrevious rotate even though the previous secret still signs, which stops it right away
     *
     * @throws \Illuminate\Validation\ValidationException when the previous secret still signs and may not be replaced
     */
    public function handle(Endpoint $endpoint, int $overlapHours = self::DEFAULT_OVERLAP_HOURS, bool $replacePrevious = false): Endpoint
    {
        // Decided on the secrets as stored, a rotation by someone else since the caller loaded the endpoint counts too
        $rotated = DB::transaction(static function () use ($endpoint, $overlapHours, $replacePrevious): Endpoint {
            $locked = Endpoint::query()->lockForUpdate()->findOrFail($endpoint->getKey());

            if (!$replacePrevious && $locked->hasActivePreviousSecret()) {
                throw ValidationException::withMessages([
                    'secret' => "The previous secret still signs deliveries until {$locked->previous_secret_expires_at->toDayDateTimeString()}. Rotating again stops it right away, so receivers that still use it reject deliveries.",
                ]);
            }

            $locked->forceFill([
                'previous_secret'            => $overlapHours > 0 ? $locked->secret : null,
                'previous_secret_expires_at' => $overlapHours > 0 ? now()->addHours($overlapHours) : null,
                'secret'                     => Signer::generateSecret(),
            ])->save();

            return $locked;
        });

        // The caller's copy shows the new secret too
        $endpoint->setRawAttributes($rotated->getAttributes(), sync: true);

        return $rotated;
    }
}
