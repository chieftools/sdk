<?php

namespace ChiefTools\SDK\Mail;

use Illuminate\Support\HtmlString;
use Illuminate\Contracts\Support\Htmlable;

/**
 * @internal
 */
final class MarkdownBody
{
    public const FULL_BLEED_START = 'chief-mail:full-bleed:start';
    public const FULL_BLEED_END   = 'chief-mail:full-bleed:end';

    /** @return list<array{content: \Illuminate\Support\HtmlString, fullBleed: bool}>|null */
    public static function segments(
        Htmlable|string $html,
        string $startMarker,
        string $endMarker,
    ): ?array {
        $html = $html instanceof Htmlable ? $html->toHtml() : $html;

        $startCount = substr_count($html, $startMarker);

        if ($startCount === 0 || $startCount !== substr_count($html, $endMarker)) {
            return null;
        }

        $parts = preg_split(
            '/(' . preg_quote($startMarker, '/') . '|' . preg_quote($endMarker, '/') . ')/',
            $html,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY,
        );

        if ($parts === false) {
            return null;
        }

        $segments    = [];
        $isFullBleed = false;

        foreach ($parts as $part) {
            if ($part === $startMarker) {
                if ($isFullBleed) {
                    return null;
                }

                $isFullBleed = true;

                continue;
            }

            if ($part === $endMarker) {
                if (!$isFullBleed) {
                    return null;
                }

                $isFullBleed = false;

                continue;
            }

            if (trim($part) === '') {
                continue;
            }

            $segments[] = [
                'content'   => new HtmlString($part),
                'fullBleed' => $isFullBleed,
            ];
        }

        return $isFullBleed ? null : $segments;
    }
}
