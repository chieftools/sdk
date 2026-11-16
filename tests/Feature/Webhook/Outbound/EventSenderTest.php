<?php

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Exceptions;
use ChiefTools\SDK\Webhook\Outbound\Webhooks;
use Tests\Fixtures\Webhooks\ExampleEventType;
use ChiefTools\SDK\Webhook\Outbound\EventSender;
use ChiefTools\SDK\Webhook\Outbound\Models\Event;
use ChiefTools\SDK\Webhook\Outbound\Models\Delivery;
use ChiefTools\SDK\Webhook\Outbound\Jobs\SendDelivery;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use ChiefTools\SDK\Webhook\Outbound\Testing\Webhooks as WebhookTesting;

uses(LazilyRefreshDatabase::class);

it('sends the rendered payload to the team\'s subscribed endpoints', function () {
    Queue::fake([SendDelivery::class]);
    $team = createTeamContextTeam('harbor');
    WebhookTesting::subscribe($team, [ExampleEventType::LANTERN_LIT]);

    lanternSender()->lanternLit($team->getKey(), 'lan_1');

    expect(WebhookTesting::sent())->toHaveCount(1)
        ->and(WebhookTesting::sent()[0])->toMatchArray(['type' => 'lantern.lit', 'data' => ['object' => ['id' => 'lan_1'], 'lit_at' => '2026-11-16T08:00:00Z']]);
});

it('does not render the payload when no endpoint receives the event', function () {
    $team     = createTeamContextTeam('harbor');
    $rendered = false;
    WebhookTesting::subscribe($team, [ExampleEventType::HARBOR_FLOODED]);

    lanternSender()->lanternLit($team->getKey(), 'lan_1', static function () use (&$rendered): void {
        $rendered = true;
    });

    expect($rendered)->toBeFalse()
        ->and(Event::query()->exists())->toBeFalse();
});

it('reports a failure instead of throwing it at the caller', function () {
    Exceptions::fake();
    $team = createTeamContextTeam('harbor');
    WebhookTesting::subscribe($team);

    lanternSender()->lanternLit($team->getKey(), 'lan_1', static fn () => throw new RuntimeException('Synthetic render failure.'));

    Exceptions::assertReported(static fn (RuntimeException $exception): bool => $exception->getMessage() === 'Synthetic render failure.');
});

it('sends to one endpoint whether it subscribed or not and throws what fails with to()', function () {
    Queue::fake([SendDelivery::class]);
    $team     = createTeamContextTeam('harbor');
    $endpoint = WebhookTesting::subscribe($team, [ExampleEventType::HARBOR_FLOODED]);
    $sender   = lanternSender()->to($endpoint);

    $sender->lanternLit($team->getKey(), 'lan_1');

    expect(Delivery::query()->sole()->endpoint_id)->toBe($endpoint->id)
        ->and(fn () => $sender->lanternLit($team->getKey(), 'lan_2', static fn () => throw new RuntimeException('Synthetic render failure.')))
        ->toThrow(RuntimeException::class, 'Synthetic render failure.');
});

/** An app's sender with one event, rendering a lantern and calling the given hook while it renders. */
function lanternSender(): EventSender
{
    return new readonly class(app(Webhooks::class)) extends EventSender
    {
        public function lanternLit(int $teamId, string $id, ?Closure $whileRendering = null): void
        {
            $this->send(ExampleEventType::LANTERN_LIT, $teamId, static function () use ($id, $whileRendering): array {
                $whileRendering?->__invoke();

                return ['id' => $id];
            }, ['lit_at' => '2026-11-16T08:00:00Z']);
        }
    };
}
