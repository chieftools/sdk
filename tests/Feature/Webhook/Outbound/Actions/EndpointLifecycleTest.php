<?php

use Illuminate\Support\Facades\Queue;
use ChiefTools\SDK\Webhook\Outbound\Webhooks;
use Tests\Fixtures\Webhooks\ExampleEventType;
use Illuminate\Validation\ValidationException;
use ChiefTools\SDK\Webhook\Outbound\Models\Event;
use ChiefTools\SDK\Webhook\Outbound\Models\Delivery;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;
use ChiefTools\SDK\Webhook\Outbound\Jobs\SendDelivery;
use ChiefTools\SDK\Webhook\Outbound\Actions\ResendEvent;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use ChiefTools\SDK\Webhook\Outbound\Actions\RotateSecret;
use ChiefTools\SDK\Webhook\Outbound\Enums\DeliveryStatus;
use ChiefTools\SDK\Webhook\Outbound\Enums\DisabledReason;
use ChiefTools\SDK\Webhook\Outbound\Enums\EndpointStatus;
use ChiefTools\SDK\Webhook\Outbound\Actions\SendTestEvent;
use ChiefTools\SDK\Webhook\Outbound\Actions\EnableEndpoint;
use ChiefTools\SDK\Webhook\Outbound\Actions\ResendDelivery;
use ChiefTools\SDK\Webhook\Outbound\Actions\UpdateEndpoint;
use ChiefTools\SDK\Webhook\Outbound\Actions\DisableEndpoint;

uses(LazilyRefreshDatabase::class);

beforeEach(fn () => resolveWebhookHostsTo());

describe('update', function () {
    it('changes the URL, description and events', function () {
        $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));

        app(UpdateEndpoint::class)->handle($endpoint, [
            'url'         => 'https://hooks.example.com/v2',
            'description' => '',
            'events'      => [ExampleEventType::HARBOR_FLOODED->value],
        ]);

        expect($endpoint->refresh())->url->toBe('https://hooks.example.com/v2')
            ->description->toBeNull()
            ->events->toBe(['harbor.flooded']);
    });
});

describe('rotate secret', function () {
    it('keeps signing with the old secret for a day', function () {
        $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00'));
        $endpoint  = createWebhookEndpoint(createTeamContextTeam('lanterns'));
        $oldSecret = $endpoint->secret;

        app(RotateSecret::class)->handle($endpoint);

        expect($endpoint->refresh())->secret->not->toBe($oldSecret)
            ->previous_secret->toBe($oldSecret)
            ->previous_secret_expires_at->format('Y-m-d H:i:s')->toBe('2026-11-08 12:00:00')
            ->signingSecrets()
            ->toBe([$endpoint->secret, $oldSecret]);
    });

    it('refuses to rotate again while the previous secret still signs', function () {
        $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
        app(RotateSecret::class)->handle($endpoint);
        $secret   = $endpoint->refresh()->secret;

        expect(static fn () => app(RotateSecret::class)->handle($endpoint))->toThrow(ValidationException::class, 'Rotating again stops it right away')
            ->and($endpoint->refresh()->secret)->toBe($secret);
    });

    it('replaces the previous secret when asked to rotate again anyway', function () {
        $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
        $first    = $endpoint->secret;
        app(RotateSecret::class)->handle($endpoint);
        $second   = $endpoint->refresh()->secret;

        app(RotateSecret::class)->handle($endpoint, replacePrevious: true);

        expect($endpoint->refresh()->signingSecrets())->toBe([$endpoint->secret, $second])
            ->not->toContain($first);
    });

    it('rotates again without asking once the previous secret expired', function () {
        $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
        app(RotateSecret::class)->handle($endpoint);
        $this->travel(25)->hours();

        app(RotateSecret::class)->handle($endpoint->refresh());

        expect($endpoint->refresh()->hasActivePreviousSecret())->toBeTrue();
    });

    it('stops signing with the old secret after it expires', function () {
        $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
        app(RotateSecret::class)->handle($endpoint);

        $this->travel(25)->hours();

        expect($endpoint->refresh()->signingSecrets())->toBe([$endpoint->secret]);
    });
});

describe('disable and enable', function () {
    it('cancels pending deliveries when disabled by hand', function () {
        Queue::fake([SendDelivery::class]);
        $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
        app(Webhooks::class)->dispatch($endpoint->team_id, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);

        app(DisableEndpoint::class)->handle($endpoint);

        expect($endpoint->refresh())->status->toBe(EndpointStatus::DISABLED)
            ->disabled_reason->toBe(DisabledReason::MANUAL);
        expect(Delivery::query()->sole()->status)->toBe(DeliveryStatus::CANCELLED);
    });

    it('starts over with a clean failure record when enabled', function () {
        $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'), [
            'status'              => EndpointStatus::DISABLED,
            'disabled_reason'     => DisabledReason::FAILING,
            'disabled_at'         => now(),
            'failing_since'       => now()->subDays(5),
            'failure_notified_at' => now(),
        ]);

        app(EnableEndpoint::class)->handle($endpoint);

        expect($endpoint->refresh())->status->toBe(EndpointStatus::ENABLED)
            ->disabled_reason->toBeNull()
            ->failing_since->toBeNull()
            ->failure_notified_at->toBeNull();
    });
});

describe('resend delivery', function () {
    it('queues a manual attempt of the delivery whatever its state', function (DeliveryStatus $status) {
        Queue::fake([SendDelivery::class]);
        $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
        app(Webhooks::class)->dispatch($endpoint->team_id, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);
        $delivery = Delivery::query()->sole();
        $delivery->forceFill(['status' => $status, 'attempts' => 3, 'next_attempt_at' => null])->save();

        app(ResendDelivery::class)->handle($delivery);

        Queue::assertPushed(SendDelivery::class, fn (SendDelivery $job) => $job->deliveryId === $delivery->id && $job->attempts === 3 && $job->manual);
        expect($delivery->refresh())->status->toBe($status)
            ->next_attempt_at->toBeNull();
    })->with([DeliveryStatus::FAILED, DeliveryStatus::SUCCEEDED, DeliveryStatus::CANCELLED]);

    it('refuses to resend to a disabled endpoint', function () {
        Queue::fake([SendDelivery::class]);
        $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
        app(Webhooks::class)->dispatch($endpoint->team_id, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);
        $endpoint->forceFill(['status' => EndpointStatus::DISABLED])->save();

        app(ResendDelivery::class)->handle(Delivery::query()->sole());
    })->throws(ValidationException::class, 'Enable the endpoint');
});

describe('resend event', function () {
    it('resends on the existing delivery to the endpoint', function () {
        Queue::fake([SendDelivery::class]);
        $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
        app(Webhooks::class)->dispatch($endpoint->team_id, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);
        $event    = Event::query()->sole();

        $delivery = app(ResendEvent::class)->handle($event, $endpoint);

        expect(Delivery::query()->where('event_id', $event->id)->sole()->id)->toBe($delivery->id);
        Queue::assertPushed(SendDelivery::class, fn (SendDelivery $job) => $job->manual);
    });

    it('sends the event as a first delivery to an endpoint that never received it', function () {
        Queue::fake([SendDelivery::class]);
        $team    = createTeamContextTeam('lanterns');
        createWebhookEndpoint($team, ['events' => [ExampleEventType::LANTERN_LIT->value]]);
        $harbors = createWebhookEndpoint($team, ['events' => [ExampleEventType::HARBOR_FLOODED->value]]);
        app(Webhooks::class)->dispatch($team, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);

        $delivery = app(ResendEvent::class)->handle(Event::query()->sole(), $harbors);

        expect($delivery)->endpoint_id->toBe($harbors->id)
            ->attempts->toBe(0);
    });

    it('refuses to send an event to an endpoint of another team', function () {
        Queue::fake([SendDelivery::class]);
        $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
        app(Webhooks::class)->dispatch($endpoint->team_id, ExampleEventType::LANTERN_LIT, ['id' => 'lan_1']);
        $other    = createWebhookEndpoint(createTeamContextTeam('elsewhere'));

        app(ResendEvent::class)->handle(Event::query()->sole(), $other);
    })->throws(ValidationException::class, 'another team');
});

describe('test event', function () {
    it('sends a test event to the endpoint even when it subscribed to other events', function () {
        Queue::fake([SendDelivery::class]);
        $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'), ['events' => [ExampleEventType::HARBOR_FLOODED->value]]);

        $delivery = app(SendTestEvent::class)->handle($endpoint);

        expect($delivery->event)->type->toBe('webhook_endpoint.test')
            ->and($delivery->event->decodedPayload()['data']['object'])->toBe([
                'id'          => $endpoint->formatted_id,
                'url'         => 'https://hooks.example.com/chief',
                'description' => null,
            ]);
        Queue::assertPushed(SendDelivery::class, 1);
    });
});

describe('rotations at the same time', function () {
    it('keeps the secret another rotation just handed out signing', function () {
        $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
        $first    = Endpoint::query()->findOrFail($endpoint->id);
        $second   = Endpoint::query()->findOrFail($endpoint->id);

        $handedOut = app(RotateSecret::class)->handle($first)->secret;
        app(RotateSecret::class)->handle($second, replacePrevious: true);

        expect($endpoint->refresh()->signingSecrets())->toBe([$endpoint->secret, $handedOut])
            ->and($second->secret)->toBe($endpoint->secret);
    });

    it('refuses a rotation from a copy loaded before another rotation', function () {
        $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
        $stale    = Endpoint::query()->findOrFail($endpoint->id);
        app(RotateSecret::class)->handle($endpoint);

        app(RotateSecret::class)->handle($stale);
    })->throws(ValidationException::class, 'Rotating again stops it right away');
});
