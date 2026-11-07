<?php

use ChiefTools\SDK\Webhook\Outbound\Support\BodyFormatter;

it('pretty-prints JSON bodies', function () {
    expect(BodyFormatter::pretty('{"id":"event_1","data":{"ok":true}}'))->toBe(<<<'JSON'
        {
            "id": "event_1",
            "data": {
                "ok": true
            }
        }
        JSON);
});

it('highlights keys, strings, numbers and literals and escapes everything', function () {
    $html = (string)BodyFormatter::highlight('{"a<b":"<script>","n":-1.5,"t":null}');

    expect($html)->toContain('<span class="text-sky-700 dark:text-sky-300">&quot;a&lt;b&quot;</span>:')
        ->toContain('<span class="text-emerald-700 dark:text-emerald-300">&quot;&lt;script&gt;&quot;</span>')
        ->toContain('<span class="text-amber-700 dark:text-amber-300">-1.5</span>')
        ->toContain('<span class="text-purple-700 dark:text-purple-300">null</span>')
        ->not->toContain('<script>');
});

it('shows other bodies as escaped text', function (?string $body, string $html) {
    expect((string)BodyFormatter::highlight($body))->toBe($html);
})->with([
    'HTML'       => ['<h1>Bad gateway</h1>', '&lt;h1&gt;Bad gateway&lt;/h1&gt;'],
    'plain JSON' => ['"ok"', '&quot;ok&quot;'],
    'empty'      => [null, ''],
]);
