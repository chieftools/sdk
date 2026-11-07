<?php

namespace ChiefTools\SDK\Webhook\Outbound\Http\Controllers\Deliveries;

use Dedoc\Scramble\Attributes\Group;
use ChiefTools\SDK\Webhook\Outbound\Models\Delivery;
use ChiefTools\SDK\Webhook\Outbound\Enums\DeliveryStatus;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use ChiefTools\SDK\Webhook\Outbound\Http\Resources\DeliveryResource;
use ChiefTools\SDK\Webhook\Outbound\Http\Concerns\ResolvesWebhookTeam;
use ChiefTools\SDK\Webhook\Outbound\Http\Requests\ListDeliveriesRequest;

#[Group('Webhooks')]
class Index
{
    use ResolvesWebhookTeam;

    /**
     * List an endpoint's deliveries.
     *
     * List the deliveries of events to an endpoint, newest first. Deliveries are kept for 30 days.
     *
     * @operationId webhooks.deliveries.index
     */
    public function __invoke(ListDeliveriesRequest $request, string $endpoint): AnonymousResourceCollection
    {
        $model = $this->findEndpoint($endpoint);

        $deliveries = Delivery::query()
            ->where('endpoint_id', $model->getKey())
            ->when($request->has('status'), static fn ($query) => $query->where('status', $request->enum('status', DeliveryStatus::class)))
            ->when($request->has('type'), static fn ($query) => $query->whereRelation('event', 'type', $request->string('type')->toString()))
            ->with(['endpoint:id', 'event:id,type'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($request->perPage());

        return DeliveryResource::collection($deliveries);
    }
}
