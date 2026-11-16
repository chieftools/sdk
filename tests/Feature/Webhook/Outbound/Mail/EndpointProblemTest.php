<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use ChiefTools\SDK\Webhook\Outbound\Mail\EndpointFailing;
use ChiefTools\SDK\Webhook\Outbound\Mail\EndpointDisabled;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Route::get('team/{team_hint}/settings/webhooks/{endpointId}', static fn () => '')->name('webhooks.endpoint');
    Route::get('team/{team_hint}/dashboard', static fn () => '')->name('dashboard');
    config(['app.name' => 'Lantern Chief']);
});

it('tells when the endpoint started failing, how long deliveries are retried and when it disables', function () {
    $this->travelTo(Carbon\CarbonImmutable::parse('2026-11-16 12:00:00'));
    $team     = createTeamContextTeam('harbor');
    $endpoint = createWebhookEndpoint($team, ['failing_since' => now()->subHours(6)]);

    $mail = new EndpointFailing($team, $endpoint, 'https://lantern.chief.test/settings#notifications');

    $mail->assertHasSubject('⚠️ Webhooks to hooks.example.com are failing')
        ->assertSeeInText('started failing 6 hours ago')
        ->assertSeeInText('Lantern Chief retries failed deliveries for about 3 days')
        ->assertSeeInText('within 15 seconds')
        ->assertSeeInText('disabled after 5 days')
        ->assertSeeInHtml(route('webhooks.endpoint', ['team_hint' => 'harbor', 'endpointId' => $endpoint->formatted_id]))
        ->assertSeeInHtml(route('dashboard', ['team_hint' => 'harbor']));

    expect(array_column($mail->content()->with['quickLinks'], 'url', 'title'))->toBe([
        'Endpoint'              => route('webhooks.endpoint', ['team_hint' => 'harbor', 'endpointId' => $endpoint->formatted_id]),
        'Dashboard'             => route('dashboard', ['team_hint' => 'harbor']),
        'Notification settings' => 'https://lantern.chief.test/settings#notifications',
    ]);
});

it('tells that a disabled endpoint receives no events until it is enabled', function () {
    $team     = createTeamContextTeam('harbor');
    $endpoint = createWebhookEndpoint($team, ['url' => 'https://hooks.example.com/chief']);

    (new EndpointDisabled($team, $endpoint))
        ->assertHasSubject('🛑 Webhooks to hooks.example.com are disabled')
        ->assertSeeInText('failed for 5 days, so the endpoint is disabled');
});

it('makes a send with the same idempotency key the same message', function () {
    $team     = createTeamContextTeam('harbor');
    $endpoint = createWebhookEndpoint($team);

    $headers = (new EndpointFailing($team, $endpoint))->setIdempotencyKey('lantern.webhooks/failing/1')->headers();

    expect($headers->text)->toBe(['Idempotency-Key' => 'lantern.webhooks/failing/1'])
        ->and($headers->messageId)->toStartWith(hash('xxh128', 'lantern.webhooks/failing/1') . '@');
});
