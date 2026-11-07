<?php

namespace ChiefTools\SDK\Webhook\Outbound\Support;

use Illuminate\Support\HtmlString;

/**
 * Shows request and response bodies for inspection: JSON pretty-printed and highlighted, anything else as plain text.
 */
final class BodyFormatter
{
    private const string TOKENS = '/(?<string>"(?:\\\\.|[^"\\\\])*")(?<colon>\s*:)?|(?<literal>\b(?:true|false|null)\b)|(?<number>-?\b\d+(?:\.\d+)?(?:[eE][+-]?\d+)?\b)/';

    public static function isJson(?string $body): bool
    {
        return $body !== null && json_validate($body) && in_array(ltrim($body)[0] ?? '', ['{', '['], true);
    }

    /** The body pretty-printed when it is JSON, as is otherwise. */
    public static function pretty(?string $body): string
    {
        if ($body === null || !self::isJson($body)) {
            return (string)$body;
        }

        return json_encode(json_decode($body), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /** The body as escaped HTML, with the keys, strings, numbers and literals of a JSON body in their own classes. */
    public static function highlight(?string $body): HtmlString
    {
        $pretty = self::pretty($body);

        if (!self::isJson($body)) {
            return new HtmlString(e($pretty));
        }

        $html   = '';
        $offset = 0;

        preg_match_all(self::TOKENS, $pretty, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL);

        foreach ($matches as $match) {
            [$token, $position] = $match[0];

            $html .= e(substr($pretty, $offset, $position - $offset));

            $html .= match (true) {
                $match['string'][0] !== null && $match['colon'][0] !== null => '<span class="text-sky-700 dark:text-sky-300">' . e($match['string'][0]) . '</span>' . e($match['colon'][0]),
                $match['string'][0] !== null                                => '<span class="text-emerald-700 dark:text-emerald-300">' . e($token) . '</span>',
                $match['literal'][0] !== null                               => '<span class="text-purple-700 dark:text-purple-300">' . e($token) . '</span>',
                default                                                     => '<span class="text-amber-700 dark:text-amber-300">' . e($token) . '</span>',
            };

            $offset = $position + strlen($token);
        }

        return new HtmlString($html . e(substr($pretty, $offset)));
    }
}
