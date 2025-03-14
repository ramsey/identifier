<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is open source software: you can distribute
 * it and/or modify it under the terms of the MIT License
 * (the "License"). You may not use this file except in
 * compliance with the License.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Service\BytesGenerator;

use DateTimeInterface;
use Psr\Clock\ClockInterface as Clock;
use Ramsey\Identifier\Service\Clock\SystemClock;

use function assert;
use function hash;
use function pack;
use function random_bytes;
use function substr;
use function unpack;

/**
 * A bytes generator that ensures the bytes generated are always greater than
 * the value of the previous bytes generated
 *
 * Code and concepts within this class are borrowed from the symfony/uid package
 * and are used under the terms of the MIT license distributed with symfony/uid.
 *
 * symfony/uid is copyright (c) Fabien Potencier.
 *
 * @link https://symfony.com/components/Uid Symfony Uid component
 * @link https://github.com/symfony/uid/blob/v7.2.0/UuidV7.php Symfony UuidV7 class
 * @link https://github.com/symfony/uid/blob/7.2/LICENSE MIT License
 */
final class MonotonicBytesGenerator implements BytesGenerator
{
    private static ?int $time = null;
    private static ?string $seed = null;
    private static int $seedIndex = 0;

    /** @var int[] */
    private static array $rand = [];

    /** @var int[] */
    private static array $seedParts;

    public function __construct(
        private readonly BytesGenerator $bytesGenerator = new RandomBytesGenerator(),
        private readonly Clock $clock = new SystemClock(),
    ) {
    }

    public function bytes(int $length = 16, ?DateTimeInterface $dateTime = null): string
    {
        $argDateTime = $dateTime;
        $dateTime = $argDateTime ?? $this->clock->now();

        $time = (int) $dateTime->format('Uv');

        if (self::$time === null || $time > self::$time || ($argDateTime !== null && $time !== self::$time)) {
            $this->randomize($time);
        } else {
            $time = $this->increment();
        }

        $bytes = substr(pack('J', $time), -6)
            . pack('n*', self::$rand[1], self::$rand[2], self::$rand[3], self::$rand[4], self::$rand[5]);

        /** @var non-empty-string */
        return match (true) {
            $length > 16 => $bytes . random_bytes($length - 16),
            $length < 16 => substr($bytes, 0, $length),
            default => $bytes,
        };
    }

    private function randomize(int $time): void
    {
        if (self::$seed === null) {
            $seed = $this->bytesGenerator->bytes(16);
            self::$seed = $seed;
        } else {
            $seed = $this->bytesGenerator->bytes(10);
        }

        /** @var int[] $rand */
        $rand = unpack('n*', $seed);
        $rand[1] &= 0x03ff;

        self::$rand = $rand;
        self::$time = $time;
    }

    /**
     * Special thanks to Nicolas Grekas for sharing the following information:
     *
     * Within the same ms, we increment the rand part by a random 24-bit number.
     *
     * Instead of getting this number from random_bytes(), which is slow, we get
     * it by sha512-hashing self::$seed. This produces 64 bytes of entropy,
     * which we need to split in a list of 24-bit numbers. unpack() first splits
     * them into 16 x 32-bit numbers; we take the first byte of each of these
     * numbers to get 5 extra 24-bit numbers. Then, we consume those numbers
     * one-by-one and run this logic every 21 iterations.
     *
     * self::$rand holds the random part of the UUID, split into 5 x 16-bit
     * numbers for x86 portability. We increment this random part by the next
     * 24-bit number in the self::$seedParts list and decrement self::$seedIndex.
     */
    private function increment(): int
    {
        if (self::$seedIndex === 0 && self::$seed !== null) {
            self::$seed = hash('sha512', self::$seed, true);

            /** @var int[] $s */
            $s = unpack('l*', self::$seed);
            $s[] = ($s[1] >> 8 & 0xff0000) | ($s[2] >> 16 & 0xff00) | ($s[3] >> 24 & 0xff);
            $s[] = ($s[4] >> 8 & 0xff0000) | ($s[5] >> 16 & 0xff00) | ($s[6] >> 24 & 0xff);
            $s[] = ($s[7] >> 8 & 0xff0000) | ($s[8] >> 16 & 0xff00) | ($s[9] >> 24 & 0xff);
            $s[] = ($s[10] >> 8 & 0xff0000) | ($s[11] >> 16 & 0xff00) | ($s[12] >> 24 & 0xff);
            $s[] = ($s[13] >> 8 & 0xff0000) | ($s[14] >> 16 & 0xff00) | ($s[15] >> 24 & 0xff);

            self::$seedParts = $s;
            self::$seedIndex = 21;
        }

        self::$rand[5] = 0xffff & $carry = self::$rand[5] + 1 + (self::$seedParts[self::$seedIndex--] & 0xffffff);
        self::$rand[4] = 0xffff & $carry = self::$rand[4] + ($carry >> 16);
        self::$rand[3] = 0xffff & $carry = self::$rand[3] + ($carry >> 16);
        self::$rand[2] = 0xffff & $carry = self::$rand[2] + ($carry >> 16);
        self::$rand[1] += $carry >> 16;

        assert(self::$time !== null);

        if (0xfc00 & self::$rand[1]) {
            $this->randomize(self::$time + 1);
        }

        return self::$time;
    }
}
