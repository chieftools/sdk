<?php

use Illuminate\Support\HtmlString;

it('escapes flashed alert text', function (string $key, mixed $value) {
    session()->flash($key, $value);

    $html = view('chief::partial.alert')->render();

    expect($html)
        ->toContain('&lt;img src=x onerror=alert(1)&gt;')
        ->not->toContain('<img src=x onerror=alert(1)>');
})->with([
    'message' => ['message', ['text' => 'Failed <img src=x onerror=alert(1)>', 'type' => 'warning']],
    'status'  => ['status', 'Sent <img src=x onerror=alert(1)>'],
]);

it('escapes replacement values in flashed alert text', function () {
    session()->flash('message', ['text' => 'Hello :name!', 'data' => ['name' => '<img src=x onerror=alert(1)>']]);

    $html = view('chief::partial.alert')->render();

    expect($html)
        ->toContain('Hello &lt;img src=x onerror=alert(1)&gt;!')
        ->not->toContain('<img src=x onerror=alert(1)>');
});

it('renders trusted HTML flashed as an HtmlString', function () {
    session()->flash('message', ['text' => new HtmlString('Review your <a href="https://example.test/security">security settings</a>.')]);

    $html = view('chief::partial.alert')->render();

    expect($html)->toContain('Review your <a href="https://example.test/security">security settings</a>.');
});
