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

use Brick\Math\BigInteger;
use DateTimeInterface;
use Ramsey\Identifier\Service\Clock\SystemClock;
use Ramsey\Identifier\Service\Os\Os;
use Ramsey\Identifier\Service\Os\PhpOs;
use StellaMaris\Clock\ClockInterface as Clock;

use function hash;
use function pack;
use function random_bytes;
use function str_pad;
use function strlen;
use function substr;
use function substr_replace;
use function unpack;

use const STR_PAD_LEFT;

/**
 * A bytes generator that ensures the bytes it generates are always greater than
 * the value of the previous bytes it generated
 *
 * Code and concepts within this class are borrowed from the symfony/uid package
 * and are used under the terms of the MIT license distributed with symfony/uid.
 *
 * symfony/uid is copyright (c) Fabien Potencier.
 *
 * @link https://symfony.com/components/Uid Symfony Uid component
 * @link https://github.com/symfony/uid/blob/4f9f537e57261519808a7ce1d941490736522bbc/UuidV7.php Symfony UuidV7 class
 * @link https://github.com/symfony/uid/blob/6.2/LICENSE MIT License
 */
final class MonotonicBytesGenerator implements BytesGenerator
{
    private static string $time = '';
    private static ?string $seed = null;
    private static int $seedIndex = 0;

    /** @var int[] */
    private static array $rand = [];

    /** @var int[] */
    private static array $seedParts;

    public function __construct(
        private readonly BytesGenerator $bytesGenerator = new RandomBytesGenerator(),
        private readonly Clock $clock = new SystemClock(),
        private readonly Os $os = new PhpOs(),
    ) {
    }

    public function bytes(int $length = 16, ?DateTimeInterface $dateTime = null): string
    {
        $argDateTime = $dateTime;
        $dateTime = $argDateTime ?? $this->clock->now();

        $time = $dateTime->format('Uv');

        if ($time > self::$time || ($argDateTime !== null && $time !== self::$time)) {
            $this->randomize($time);
        } else {
            $time = $this->increment();
        }

        if ($this->os->getIntSize() >= 8) {
            $time = substr(pack('J', (int) $time), -6);
        } else {
            $time = str_pad(BigInteger::of($time)->toBytes(false), 6, "\x00", STR_PAD_LEFT);
        }

        /** @var non-empty-string $bytes */
        $bytes = $time . pack('n*', self::$rand[1], self::$rand[2], self::$rand[3], self::$rand[4], self::$rand[5]);

        if ($length === 16) {
            return $bytes;
        } elseif ($length <= 16) {
            /** @var non-empty-string */
            return substr($bytes, 0, $length);
        } else {
            // If the caller requested more bytes, add more bytes.
            return $bytes . random_bytes($length - 16);
        }
    }

    private function randomize(string $time): void
    {
        if (self::$seed === null) {
            $seed = $this->bytesGenerator->bytes();
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
     * 24-bit number in the self::$seedParts list and decrement
     * self::$seedIndex.
     *
     * @link https://twitter.com/nicolasgrekas/status/1583356938825261061 Tweet from Nicolas Grekas
     */
    private function increment(): string
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

        self::$rand[5] = 0xffff & $carry = self::$rand[5] + (self::$seedParts[self::$seedIndex--] & 0xffffff);
        self::$rand[4] = 0xffff & $carry = self::$rand[4] + ($carry >> 16);
        self::$rand[3] = 0xffff & $carry = self::$rand[3] + ($carry >> 16);
        self::$rand[2] = 0xffff & $carry = self::$rand[2] + ($carry >> 16);
        self::$rand[1] += $carry >> 16;

        if (0xfc00 & self::$rand[1]) {
            $time = self::$time;
            $mtime = (int) substr($time, -9);

            if ($this->os->getIntSize() >= 8 || strlen($time) < 10) {
                $time = (string) ((int) $time + 1);
            } elseif ($mtime === 999999999) {
                $time = (1 + (int) substr($time, 0, -9)) . '000000000';
            } else {
                $mtime++;
                $time = substr_replace($time, str_pad((string) $mtime, 9, '0', STR_PAD_LEFT), -9);
            }

            $this->randomize($time);
        }

        return self::$time;
    }
}
