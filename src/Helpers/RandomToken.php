<?php

namespace IronGate\Chief\Helpers;

use Tuupola\Base62;
use IronGate\Chief\Exceptions\RandomToken\InvalidTokenFormatException;
use IronGate\Chief\Exceptions\RandomToken\InvalidTokenLengthException;
use IronGate\Chief\Exceptions\RandomToken\InvalidTokenPrefixException;
use IronGate\Chief\Exceptions\RandomToken\InvalidTokenChecksumException;

class RandomToken
{
    private const PARSE_REGEX = '/^(?<prefix>[a-zA-Z0-9]{3,6})_(?<random>[a-zA-Z0-9]{30,242})(?<checksum>[a-zA-Z0-9]{6})$/';

    private const DEFAULT_LENGTH  = 30;
    private const MINIMUM_LENGTH  = 30;
    private const MAXIMUM_LENGTH  = 242;
    private const CHECKSUM_LENGTH = 6;

    private function __construct(
        public readonly string $prefix,
        public readonly string $random,
        public readonly string $checksum,
    ) {
    }

    public function __toString(): string
    {
        return "{$this->prefix}_{$this->random}{$this->checksum}";
    }

    public function cacheKey(): string
    {
        return "chief:{$this->prefix}_token:" . hash('sha256', $this->random);
    }

    public static function generate(string $prefix, int $length = self::DEFAULT_LENGTH): self
    {
        self::validatePrefix($prefix);

        $random = str_random($length);

        self::validateLength($random);

        $checksum = self::calculateChecksum($random);

        return new self($prefix, $random, $checksum);
    }

    public static function fromString(string $token): self
    {
        $matched = preg_match(self::PARSE_REGEX, $token, $matches);

        if (!$matched) {
            throw new InvalidTokenFormatException;
        }

        self::validatePrefix($matches['prefix']);
        self::validateLength($matches['random']);

        $checksum = self::calculateChecksum($matches['random']);

        if ($checksum !== $matches['checksum']) {
            throw new InvalidTokenChecksumException;
        }

        return new self($matches['prefix'], $matches['random'], $matches['checksum']);
    }

    public static function fromTrustedRandom(string $prefix, string $random): self
    {
        self::validatePrefix($prefix);

        return new self($prefix, $random, self::calculateChecksum($random));
    }

    private static function validateLength(string $prefix): void
    {
        if (strlen($prefix) >= self::MINIMUM_LENGTH && strlen($prefix) <= self::MAXIMUM_LENGTH) {
            return;
        }

        throw new InvalidTokenLengthException;
    }

    private static function validatePrefix(string $prefix): void
    {
        if (strlen($prefix) >= 3 && strlen($prefix) <= 6) {
            return;
        }

        throw new InvalidTokenPrefixException;
    }

    private static function calculateChecksum(string $string): string
    {
        $crc32 = hex2bin(hash('crc32b', $string));

        return str_pad((new Base62)->encode($crc32), self::CHECKSUM_LENGTH, '0', STR_PAD_LEFT);
    }
}
