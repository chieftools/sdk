<?php

use ChiefTools\SDK\Webhook\Outbound\EndpointPacing;

it('sends while the endpoint has room', function () {
    $sent = false;

    $wait = app(EndpointPacing::class)->attempt('01jendpoint', static function () use (&$sent): void {
        $sent = true;
    });

    expect($wait)->toBeNull()->and($sent)->toBeTrue();
});

it('waits while the requests in flight fill the endpoint', function () {
    $pacing = app(EndpointPacing::class);
    $second = $third = 'not tried';

    // A second request fits next to the first, a third does not while both still wait for their responses
    $pacing->attempt('01jendpoint', static function () use ($pacing, &$second, &$third): void {
        $second = $pacing->attempt('01jendpoint', static function () use ($pacing, &$third): void {
            $third = $pacing->attempt('01jendpoint', static fn () => null);
        });
    });

    expect($second)->toBeNull()->and($third)->toBe(1);
});

it('waits once the endpoint had its requests for this second', function () {
    config(['chief.webhooks.max_per_second_per_endpoint' => 1]);
    $pacing = app(EndpointPacing::class);

    $first  = $pacing->attempt('01jendpoint', static fn () => null);
    $second = $pacing->attempt('01jendpoint', static fn () => null);
    $other  = $pacing->attempt('01jotherendpoint', static fn () => null);

    expect($first)->toBeNull()
        ->and($second)->toBeGreaterThanOrEqual(1)
        ->and($other)->toBeNull();
});
