<?php

namespace ChiefTools\SDK\Helpers;

final class MailBrandColor
{
    private const FALLBACK_COLOR = '#34495e';
    private const SUCCESS_COLOR  = '#047857';
    private const ERROR_COLOR    = '#b91c1c';

    public static function primary(): string
    {
        return self::normalize(config('chief.brand.color'));
    }

    public static function primaryButton(): string
    {
        return self::adjustLightness(self::primary(), -0.11);
    }

    public static function primaryButtonText(): string
    {
        return self::readableTextFor(self::primaryButton());
    }

    public static function primaryHover(): string
    {
        return self::primaryButton();
    }

    public static function primaryButtonHover(): string
    {
        return self::adjustLightness(self::primaryButton(), -0.06);
    }

    public static function primaryButtonHoverText(): string
    {
        return self::readableTextFor(self::primaryButtonHover());
    }

    public static function primaryDarkHover(): string
    {
        return self::mix(self::primary(), '#ffffff', 0.16);
    }

    public static function primaryDarkButtonHover(): string
    {
        return self::mix(self::primaryButton(), '#ffffff', 0.16);
    }

    public static function primaryDarkButtonHoverText(): string
    {
        return self::readableTextFor(self::primaryDarkButtonHover());
    }

    public static function success(): string
    {
        return self::SUCCESS_COLOR;
    }

    public static function successButtonText(): string
    {
        return '#ffffff';
    }

    public static function error(): string
    {
        return self::ERROR_COLOR;
    }

    public static function errorButtonText(): string
    {
        return '#ffffff';
    }

    public static function normalize(?string $color): string
    {
        return self::normalizeOrNull($color) ?? self::FALLBACK_COLOR;
    }

    private static function normalizeOrNull(mixed $color): ?string
    {
        if (!is_string($color)) {
            return null;
        }

        $color = strtolower(trim($color));

        if (preg_match('/^#?([0-9a-f]{3})$/', $color, $matches) === 1) {
            return '#' . implode('', array_map(
                static fn (string $part): string => $part . $part,
                str_split($matches[1]),
            ));
        }

        if (preg_match('/^#?([0-9a-f]{6})$/', $color, $matches) === 1) {
            return '#' . $matches[1];
        }

        return null;
    }

    public static function readableTextFor(string $background): string
    {
        $background = self::normalize($background);

        $whiteContrast = self::contrastRatio($background, '#ffffff');
        $darkContrast  = self::contrastRatio($background, '#111827');

        return $whiteContrast >= $darkContrast ? '#ffffff' : '#111827';
    }

    private static function contrastRatio(string $first, string $second): float
    {
        $firstLuminance  = self::relativeLuminance($first);
        $secondLuminance = self::relativeLuminance($second);

        $lighter = max($firstLuminance, $secondLuminance);
        $darker  = min($firstLuminance, $secondLuminance);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    private static function mix(string $from, string $to, float $amount): string
    {
        $amount = max(0, min(1, $amount));

        [$fromRed, $fromGreen, $fromBlue] = self::rgb($from);
        [$toRed, $toGreen, $toBlue]       = self::rgb($to);

        return sprintf(
            '#%02x%02x%02x',
            self::mixChannel($fromRed, $toRed, $amount),
            self::mixChannel($fromGreen, $toGreen, $amount),
            self::mixChannel($fromBlue, $toBlue, $amount),
        );
    }

    private static function mixChannel(int $from, int $to, float $amount): int
    {
        return (int)round($from + (($to - $from) * $amount));
    }

    private static function adjustLightness(string $color, float $amount): string
    {
        [$hue, $saturation, $lightness] = self::hsl($color);

        return self::hexFromHsl($hue, $saturation, max(0, min(1, $lightness + $amount)));
    }

    /**
     * @return array{float, float, float}
     */
    private static function hsl(string $color): array
    {
        [$red, $green, $blue] = array_map(
            static fn (int $channel): float => $channel / 255,
            self::rgb($color),
        );

        $max       = max($red, $green, $blue);
        $min       = min($red, $green, $blue);
        $lightness = ($max + $min) / 2;

        if ($max === $min) {
            return [0, 0, $lightness];
        }

        $delta      = $max - $min;
        $saturation = $lightness > 0.5
            ? $delta / (2 - $max - $min)
            : $delta / ($max + $min);

        if ($max === $red) {
            $hue = (($green - $blue) / $delta) + ($green < $blue ? 6 : 0);
        } elseif ($max === $green) {
            $hue = (($blue - $red) / $delta) + 2;
        } else {
            $hue = (($red - $green) / $delta) + 4;
        }

        return [$hue / 6, $saturation, $lightness];
    }

    private static function hexFromHsl(float $hue, float $saturation, float $lightness): string
    {
        if ($saturation === 0.0) {
            $red = $green = $blue = $lightness;
        } else {
            $q = $lightness < 0.5
                ? $lightness * (1 + $saturation)
                : $lightness + $saturation - ($lightness * $saturation);
            $p = (2 * $lightness) - $q;

            $red   = self::hueToRgb($p, $q, $hue + (1 / 3));
            $green = self::hueToRgb($p, $q, $hue);
            $blue  = self::hueToRgb($p, $q, $hue - (1 / 3));
        }

        return sprintf(
            '#%02x%02x%02x',
            (int)round($red * 255),
            (int)round($green * 255),
            (int)round($blue * 255),
        );
    }

    private static function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) {
            $t++;
        }

        if ($t > 1) {
            $t--;
        }

        if ($t < 1 / 6) {
            return $p + (($q - $p) * 6 * $t);
        }

        if ($t < 1 / 2) {
            return $q;
        }

        if ($t < 2 / 3) {
            return $p + (($q - $p) * ((2 / 3) - $t) * 6);
        }

        return $p;
    }

    private static function relativeLuminance(string $color): float
    {
        [$red, $green, $blue] = self::rgb($color);

        $channels = array_map(static function (int $channel): float {
            $value = $channel / 255;

            if ($value <= 0.03928) {
                return $value / 12.92;
            }

            return (($value + 0.055) / 1.055) ** 2.4;
        }, [$red, $green, $blue]);

        return (0.2126 * $channels[0])
            + (0.7152 * $channels[1])
            + (0.0722 * $channels[2]);
    }

    /**
     * @return array{int, int, int}
     */
    private static function rgb(string $color): array
    {
        $color = ltrim(self::normalize($color), '#');

        return [
            hexdec(substr($color, 0, 2)),
            hexdec(substr($color, 2, 2)),
            hexdec(substr($color, 4, 2)),
        ];
    }
}
