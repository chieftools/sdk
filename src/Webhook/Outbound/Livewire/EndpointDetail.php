<?php

namespace ChiefTools\SDK\Webhook\Outbound\Livewire;

use Flux\Flux;
use Livewire\Component;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Livewire\Attributes\Locked;
use Illuminate\Support\Facades\Date;
use ChiefTools\SDK\Webhook\Outbound\Webhooks;
use ChiefTools\SDK\Webhook\Outbound\Models\Delivery;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;
use ChiefTools\SDK\Webhook\Outbound\Stats\EndpointStats;
use ChiefTools\SDK\Webhook\Outbound\Actions\RotateSecret;
use ChiefTools\SDK\Webhook\Outbound\Enums\DeliveryStatus;
use ChiefTools\SDK\Webhook\Outbound\Actions\SendTestEvent;
use ChiefTools\SDK\Webhook\Outbound\Actions\DeleteEndpoint;
use ChiefTools\SDK\Webhook\Outbound\Actions\EnableEndpoint;
use ChiefTools\SDK\Webhook\Outbound\Actions\UpdateEndpoint;
use ChiefTools\SDK\Webhook\Outbound\Actions\DisableEndpoint;
use ChiefTools\SDK\Webhook\Outbound\Livewire\Concerns\ManagesTeamWebhooks;

/**
 * One endpoint: its signing secret, graphs of its deliveries and their log, with the request and response of every
 * attempt in a modal.
 */
class EndpointDetail extends Component
{
    use ChoosesEvents, ManagesTeamWebhooks, WithPagination;

    public const int PER_PAGE = 25;

    #[Locked]
    public string $endpointId;

    #[Url(except: '24h')]
    public string $range = '24h';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $event = '';

    /** The signing secret once someone chose to show it, it is not sent to the browser before. */
    #[Locked]
    public ?string $revealedSecret = null;

    /** The delivery the details modal shows. */
    #[Locked]
    public ?string $detailsDeliveryId = null;

    public string $url = '';

    public string $description = '';

    public function mount(string $endpointId): void
    {
        $endpoint = $this->findEndpoint($endpointId);

        $this->endpointId = $endpoint->formatted_id;

        // An endpoint that was just added shows its secret right away
        if (session(Endpoints::REVEAL_SECRET_SESSION_KEY) === $endpoint->id) {
            $this->revealedSecret = $endpoint->secret;
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['status', 'event'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('status', 'event');
        $this->resetPage();
    }

    public function revealSecret(): void
    {
        $this->revealedSecret = $this->endpoint()->secret;
    }

    /** Asks to confirm a rotation, with a warning when it would stop the previous secret right away. */
    public function confirmRotateSecret(): void
    {
        $this->resetValidation('secret');

        Flux::modal('webhook-rotate-secret')->show();
    }

    /**
     * @param bool $replacePrevious the confirmation warned that this stops the previous secret, when someone else rotated
     *                              since it was shown the rotation is refused and the warning shows instead
     */
    public function rotateSecret(RotateSecret $rotateSecret, bool $replacePrevious = false): void
    {
        $endpoint = $rotateSecret->handle($this->endpoint(), replacePrevious: $replacePrevious);

        $this->revealedSecret = $endpoint->secret;

        Flux::modal('webhook-rotate-secret')->close();
        Flux::toast(text: 'Deliveries are signed with both secrets for 24 hours, update your receiver before then.', heading: 'Secret rotated', variant: 'success');
    }

    public function edit(): void
    {
        $endpoint = $this->endpoint();

        $this->url         = $endpoint->url;
        $this->description = (string)$endpoint->description;
        $this->resetEventChoice($endpoint);
        $this->resetValidation();

        Flux::modal('webhook-endpoint')->show();
    }

    public function save(UpdateEndpoint $updateEndpoint): void
    {
        $updateEndpoint->handle($this->endpoint(), [
            'url'         => $this->url,
            'description' => $this->description,
            'events'      => $this->chosenEvents(),
        ]);

        Flux::modal('webhook-endpoint')->close();
        Flux::toast(text: 'The endpoint receives its events at the new settings.', heading: 'Endpoint saved', variant: 'success');
    }

    public function sendTestEvent(SendTestEvent $sendTestEvent): void
    {
        $delivery = $sendTestEvent->handle($this->endpoint());

        $this->resetFilters();

        Flux::toast(text: 'It shows up in the deliveries below in a moment.', heading: 'Test event sent', variant: 'success');

        $this->showDelivery($delivery->formatted_id);
    }

    public function enable(EnableEndpoint $enableEndpoint): void
    {
        $enableEndpoint->handle($this->endpoint());

        Flux::toast(text: 'Events are sent to the endpoint again. Deliveries cancelled when it was disabled can be resent from its log.', heading: 'Endpoint enabled', variant: 'success');
    }

    public function disable(DisableEndpoint $disableEndpoint): void
    {
        $disableEndpoint->handle($this->endpoint());

        Flux::toast(text: 'No events are sent to the endpoint until you enable it again, and events in the meantime are not sent later.', heading: 'Endpoint disabled', variant: 'success');
    }

    public function delete(DeleteEndpoint $deleteEndpoint): void
    {
        $deleteEndpoint->handle($this->endpoint());

        Flux::toast(text: 'The endpoint and its deliveries were removed.', heading: 'Endpoint deleted', variant: 'success');

        $this->redirectRoute('webhooks', navigate: true);
    }

    public function showDelivery(string $deliveryId): void
    {
        $delivery = $this->findDelivery($deliveryId);

        abort_unless($delivery->endpoint_id === $this->endpoint()->id, 404);

        $this->detailsDeliveryId = $delivery->formatted_id;

        Flux::modal('webhook-delivery')->show();
    }

    public function render(Webhooks $webhooks, EndpointStats $stats): View
    {
        $endpoint = $this->endpoint();
        $range    = array_key_exists($this->range, EndpointStats::RANGES) ? $this->range : '24h';

        $deliveries = Delivery::query()
            ->where('endpoint_id', $endpoint->id)
            ->with('event:id,type,created_at')
            ->when(DeliveryStatus::tryFrom($this->status), static fn ($query, DeliveryStatus $status) => $query->where('status', $status))
            ->when($this->event !== '', fn ($query) => $query->whereHas('event', fn ($query) => $query->where('type', $this->event)))
            ->latest()
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE);

        return view('chief::webhooks.endpoint', [
            'endpoint'    => $endpoint,
            'deliveries'  => $deliveries,
            'filtered'    => $this->status !== '' || $this->event !== '',
            'summary'     => $stats->summary($endpoint, $range),
            'chart'       => self::chartRows($stats->series($endpoint, $range)),
            'days'        => EndpointStats::RANGES[$range]['bucket_hours'] >= 24,
            'eventGroups' => $webhooks->groupedEventTypes(),
            'breadcrumbs' => $this->webhookBreadcrumbs([['title' => parse_url($endpoint->url, PHP_URL_HOST) ?: $endpoint->url]]),
        ]);
    }

    private function endpoint(): Endpoint
    {
        return $this->findEndpoint($this->endpointId);
    }

    /**
     * Rows for the graphs, dated in the user's time zone but written as UTC so the chart shows that wall clock time.
     *
     * @param list<array{date: \Carbon\CarbonImmutable, attempts: int, succeeded: int, failed: int, avg_ms: int|null, max_ms: int|null}> $series
     *
     * @return list<array{date: string, succeeded: int, failed: int, avg_ms: int, max_ms: int}>
     */
    private static function chartRows(array $series): array
    {
        $timezone = config('app.timezone_user') ?: config('app.timezone');

        return array_map(static fn (array $bucket): array => [
            'date'      => Date::instance($bucket['date'])->setTimezone($timezone)->format('Y-m-d\TH:i:s\Z'),
            'succeeded' => $bucket['succeeded'],
            'failed'    => $bucket['failed'],
            'avg_ms'    => $bucket['avg_ms'] ?? 0,
            'max_ms'    => $bucket['max_ms'] ?? 0,
        ], $series);
    }
}
