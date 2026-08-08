<?php

use ChiefTools\SDK\Mail\MarkdownBody;

function full_bleed_markers(): array
{
    return [
        '<!-- ' . MarkdownBody::FULL_BLEED_START . ' -->',
        '<!-- ' . MarkdownBody::FULL_BLEED_END . ' -->',
    ];
}

test('markdown body returns no segments without full bleed content', function () {
    [$startMarker, $endMarker] = full_bleed_markers();

    expect(MarkdownBody::segments('<p>Regular message content.</p>', $startMarker, $endMarker))->toBeNull();
});

test('markdown body separates multiple full bleed sections in order', function () {
    [$startMarker, $endMarker] = full_bleed_markers();

    $segments = MarkdownBody::segments(
        '<p>Introduction</p>'
        . $startMarker . '<table><tr><td>First band</td></tr></table>' . $endMarker
        . '<p>Middle copy</p>'
        . $startMarker . '<table><tr><td>Second band</td></tr></table>' . $endMarker
        . '<p>Closing copy</p>',
        $startMarker,
        $endMarker,
    );

    expect($segments)->not->toBeNull();
    expect(array_column($segments, 'fullBleed'))->toBe([false, true, false, true, false]);
    expect(array_map(static fn (array $segment): string => (string)$segment['content'], $segments))
        ->toBe([
            '<p>Introduction</p>',
            '<table><tr><td>First band</td></tr></table>',
            '<p>Middle copy</p>',
            '<table><tr><td>Second band</td></tr></table>',
            '<p>Closing copy</p>',
        ]);
});

test('markdown body supports adjacent full bleed sections', function () {
    [$startMarker, $endMarker] = full_bleed_markers();

    $segments = MarkdownBody::segments(
        $startMarker . '<table><tr><td>First band</td></tr></table>' . $endMarker
        . $startMarker . '<table><tr><td>Second band</td></tr></table>' . $endMarker,
        $startMarker,
        $endMarker,
    );

    expect($segments)->not->toBeNull();
    expect(array_column($segments, 'fullBleed'))->toBe([true, true]);
});

test('markdown body rejects malformed full bleed markers', function (string $html) {
    [$startMarker, $endMarker] = full_bleed_markers();

    expect(MarkdownBody::segments($html, $startMarker, $endMarker))->toBeNull();
})->with([
    'missing end marker' => fn (): string => full_bleed_markers()[0] . '<p>Unclosed section</p>',
    'end before start'   => fn (): string => full_bleed_markers()[1] . '<p>Out of order</p>' . full_bleed_markers()[0],
    'nested markers'     => fn (): string => full_bleed_markers()[0] . full_bleed_markers()[0]
        . '<p>Nested section</p>' . full_bleed_markers()[1] . full_bleed_markers()[1],
]);
