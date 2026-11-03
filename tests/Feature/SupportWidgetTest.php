<?php

use Illuminate\Support\Js;

test('the support widget loads the status from the summary of the configured status page', function () {
    config(['chief.status_url' => 'https://status.example.test/']);

    $widget = view('chief::partial.support-widget')->render();

    expect($widget)->toContain('href="https://status.example.test"')
        ->toContain(Js::from('https://status.example.test/api/v2/summary.json')->toHtml())
        ->not->toContain('<iframe');
});
