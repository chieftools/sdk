<?php

use Illuminate\Support\Facades\Queue;
use ChiefTools\SDK\Webhook\Outbound\Webhooks;
use Tests\Fixtures\Webhooks\ExampleEventType;
use ChiefTools\SDK\Webhook\Outbound\Models\Event;
use ChiefTools\SDK\Webhook\Outbound\Models\Delivery;
use ChiefTools\SDK\Webhook\Outbound\Jobs\SendDelivery;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use ChiefTools\SDK\Webhook\Outbound\Enums\DeliveryStatus;

uses(LazilyRefreshDatabase::class);

it('deletes events past the retention with their deliveries', function () {
    Queue::fake([SendDelivery::class]);
    $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
    $this->travelTo(Carbon\CarbonImmutable::parse('2026-10-01 12:00:00'));
    app(Webhooks::class)->dispatch($endpoint->team_id, ExampleEventType::LANTERN_LIT, ['id' => 'lan_old']);
    app(Webhooks::class)->dispatch($endpoint->team_id, ExampleEventType::LANTERN_LIT, ['id' => 'lan_old_pending']);
    [$old, $oldPending] = Event::query()->orderBy('id')->get()->all();
    Delivery::query()->where('event_id', $old->id)->update(['status' => DeliveryStatus::SUCCEEDED]);
    $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00'));
    app(Webhooks::class)->dispatch($endpoint->team_id, ExampleEventType::LANTERN_LIT, ['id' => 'lan_new']);

    $this->artisan('chief:webhooks:prune')->assertSuccessful();

    expect(Event::query()->whereKey($old->id)->exists())->toBeFalse()
        ->and(Delivery::query()->where('event_id', $old->id)->exists())->toBeFalse()
        ->and(Event::query()->whereKey($oldPending->id)->exists())->toBeTrue()
        ->and(Event::query()->count())->toBe(2);
});

it('keeps an event past the retention while a resend of it is outstanding', function () {
    Queue::fake([SendDelivery::class]);
    $endpoint = createWebhookEndpoint(createTeamContextTeam('lanterns'));
    $this->travelTo(Carbon\CarbonImmutable::parse('2026-10-01 12:00:00'));
    app(Webhooks::class)->dispatch($endpoint->team_id, ExampleEventType::LANTERN_LIT, ['id' => 'lan_resent']);
    app(Webhooks::class)->dispatch($endpoint->team_id, ExampleEventType::LANTERN_LIT, ['id' => 'lan_done']);
    Delivery::query()->update(['status' => DeliveryStatus::SUCCEEDED, 'attempts' => 1, 'next_attempt_at' => null]);
    [$resent, $done] = Delivery::query()->orderBy('id')->get()->all();
    $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-07 12:00:00'));
    app(ChiefTools\SDK\Webhook\Outbound\Actions\ResendDelivery::class)->handle($resent);

    $this->artisan('chief:webhooks:prune')->assertSuccessful();

    $this->assertModelExists($resent);
    $this->assertModelMissing($done);
    expect(Event::query()->whereKey($resent->event_id)->exists())->toBeTrue()
        ->and(Event::query()->whereKey($done->event_id)->exists())->toBeFalse();
});
