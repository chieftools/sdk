<?php

namespace ChiefTools\SDK\Webhook\Outbound\Actions;

use ChiefTools\SDK\Entities\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use ChiefTools\SDK\Webhook\Outbound\Signing\Signer;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;
use ChiefTools\SDK\Webhook\Outbound\Enums\EndpointStatus;

/**
 * Adds an endpoint for the team within its plan's `webhook_endpoints` limit, with a new signing secret.
 */
class CreateEndpoint
{
    /**
     * @param array<string, mixed> $input `url`, `description` and `events`, see `EndpointInput::rules()`
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function handle(Team $team, array $input): Endpoint
    {
        $validated = EndpointInput::validate($input);

        // The team's row is locked while counting, so endpoints added at the same moment cannot both fit the last slot
        return DB::transaction(static function () use ($team, $validated): Endpoint {
            /** @var \ChiefTools\SDK\Entities\Team $locked */
            $locked = $team->newQuery()->whereKey($team->getKey())->lockForUpdate()->firstOrFail();

            $limit = $locked->webhookEndpointsLimit();

            if ($limit >= 0 && Endpoint::query()->where('team_id', $team->getKey())->count() >= $limit) {
                throw ValidationException::withMessages([
                    'url' => $limit === 0
                        ? 'Webhooks are not included in your plan.'
                        : "Your plan includes up to {$limit} webhook " . str('endpoint')->plural($limit) . ', remove one to add another.',
                ]);
            }

            return Endpoint::query()->create([
                'team_id' => $team->getKey(),
                'secret'  => Signer::generateSecret(),
                'status'  => EndpointStatus::ENABLED,
                ...$validated,
            ]);
        });
    }
}
