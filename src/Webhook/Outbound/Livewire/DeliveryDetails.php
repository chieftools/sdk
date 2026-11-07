<?php

namespace ChiefTools\SDK\Webhook\Outbound\Livewire;

use Flux\Flux;
use Livewire\Component;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use ChiefTools\SDK\Webhook\Outbound\Models\Attempt;
use ChiefTools\SDK\Webhook\Outbound\Models\Delivery;
use ChiefTools\SDK\Webhook\Outbound\Actions\ResendDelivery;
use ChiefTools\SDK\Webhook\Outbound\Livewire\Concerns\ManagesTeamWebhooks;

/**
 * One delivery in a modal: the timeline of its attempts and the request sent and response received of the chosen one.
 */
class DeliveryDetails extends Component
{
    use ManagesTeamWebhooks;

    #[Locked]
    public string $deliveryId;

    /** The attempt shown, the newest when none was chosen. */
    #[Locked]
    public ?string $attemptId = null;

    /** The attempts the delivery had when it was resent, to wait for the next one. */
    #[Locked]
    public ?int $resentAfterAttempts = null;

    /** `response` or `request`, the request body is the event's payload. */
    public string $tab = 'response';

    public function mount(string $deliveryId): void
    {
        $this->deliveryId = $this->findDelivery($deliveryId)->formatted_id;
    }

    public function showAttempt(string $attemptId): void
    {
        $this->attemptId = $this->delivery()->attemptLog()->whereKey($attemptId)->firstOrFail()->id;
    }

    public function resend(ResendDelivery $resendDelivery): void
    {
        $delivery = $resendDelivery->handle($this->delivery());

        $this->attemptId           = null;
        $this->resentAfterAttempts = $delivery->attempts;

        Flux::toast(text: 'The new attempt shows up here in a moment.', heading: 'Resending', variant: 'success');
    }

    public function render(): View
    {
        $delivery = $this->delivery()->load(['endpoint', 'event', 'attemptLog']);

        /** @var \ChiefTools\SDK\Webhook\Outbound\Models\Attempt|null $attempt */
        $attempt = $this->attemptId === null
            ? $delivery->attemptLog->last()
            : $delivery->attemptLog->first(fn (Attempt $attempt): bool => $attempt->id === $this->attemptId);

        return view('chief::webhooks.delivery-details', [
            'delivery' => $delivery,
            'attempt'  => $attempt,
            // Poll while an attempt can still come in: retries are scheduled, or a resend has not shown up yet
            'polls' => $delivery->isPending() || ($this->resentAfterAttempts !== null && $delivery->attempts <= $this->resentAfterAttempts),
        ]);
    }

    private function delivery(): Delivery
    {
        return $this->findDelivery($this->deliveryId);
    }
}
