<?php

use Dedoc\Scramble\Scramble;
use Tests\Fixtures\Probe\InstalledOtherClass;
use ChiefTools\SDK\Webhook\Outbound\Http\Probe\InstalledWebhooksApiClass;

beforeEach(function () {
    require_once dirname(__DIR__, 2) . '/Fixtures/vendor/ScrambleVendorProbes.php';
});

it('lets Scramble infer the webhooks API from its source when the SDK is installed in vendor', function () {
    expect(Scramble::infer()->configure())
        ->shouldAnalyzeAst(InstalledWebhooksApiClass::class)
        ->toBeTrue()
        ->shouldAnalyzeAst(InstalledOtherClass::class)
        ->toBeFalse();
});
