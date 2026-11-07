<?php

use ChiefTools\SDK\Webhook\Outbound\Signing\Signer;

it('signs like the Standard Webhooks reference example', function () {
    $signature = Signer::signature(
        id: 'msg_p5jXN8AQM9LWM0D4loKWxJek',
        timestamp: 1614265330,
        body: '{"test": 2432232314}',
        secrets: ['whsec_MfKQ9r8GKYqrTwjUPD8ILPZIo2LaLaSw'],
    );

    expect($signature)->toBe('v1,g0hM9SsE+OTPJTGt/tmIKtSyZlE3uFJELVlNIOLJ1OE=');
});

it('adds a signature per secret separated by a space', function () {
    $signature = Signer::signature(
        id: 'msg_p5jXN8AQM9LWM0D4loKWxJek',
        timestamp: 1614265330,
        body: '{"test": 2432232314}',
        secrets: ['whsec_MfKQ9r8GKYqrTwjUPD8ILPZIo2LaLaSw', 'whsec_' . base64_encode('another-signing-secret-24b')],
    );

    expect(explode(' ', $signature))->toHaveCount(2)
        ->and(explode(' ', $signature)[0])->toBe('v1,g0hM9SsE+OTPJTGt/tmIKtSyZlE3uFJELVlNIOLJ1OE=')
        ->and(explode(' ', $signature)[1])->toStartWith('v1,')->not->toBe('v1,g0hM9SsE+OTPJTGt/tmIKtSyZlE3uFJELVlNIOLJ1OE=');
});

it('generates prefixed secrets of 24 random bytes', function () {
    $secret = Signer::generateSecret();

    expect($secret)->toStartWith('whsec_')
        ->and(strlen(base64_decode(substr($secret, 6), true)))->toBe(24)
        ->and(Signer::generateSecret())->not->toBe($secret);
});

it('rejects signing without a secret', function () {
    Signer::signature('msg_1', 1614265330, '{}', []);
})->throws(InvalidArgumentException::class);
