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

namespace Ramsey\Identifier\Uuid;

use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use DateTimeInterface;
use Identifier\Uuid\Variant;
use Identifier\Uuid\Version;
use Ramsey\Identifier\Exception\InvalidArgumentException;

use function intdiv;
use function pack;
use function str_pad;
use function strlen;
use function substr;
use function unpack;

use const PHP_INT_SIZE;
use const STR_PAD_LEFT;

/**
 * Utilities for manipulating and managing UUIDs
 *
 * @internal
 */
final class Util
{
    /**
     * Bytes representation
     */
    public const FORMAT_BYTES = 16;

    /**
     * String standard representation
     */
    public const FORMAT_STRING = 36;

    /**
     * Hexadecimal representation
     */
    public const FORMAT_HEX = 32;

    /**
     * The number of 100-nanosecond intervals as bytes between the
     * Gregorian epoch 1582-10-15 00:00:00 and the Unix epoch 1970-01-01 00:00:00.
     */
    public const GREGORIAN_OFFSET_BIN = "\x01\xb2\x1d\xd2\x13\x81\x40\x00";

    /**
     * The number of 100-nanosecond intervals as a native integer between the
     * Gregorian epoch 1582-10-15 00:00:00 and the Unix epoch 1970-01-01 00:00:00.
     */
    public const GREGORIAN_OFFSET_INT = 0x01b21dd213814000;

    /**
     * A mask used with functions like {@see strspn()} to validate hexadecimal strings
     */
    public const MASK_HEX = '0123456789abcdefABCDEF';

    /**
     * A mask used with functions like {@see strspn()} to validate Max UUID strings
     */
    public const MASK_MAX = 'fF';

    /**
     * The number of 100-nanosecond intervals in one second.
     */
    public const NANOSECOND_INTERVALS = 10000000;

    /**
     * Applies the RFC 4122 version number and variant field to the 128-bit
     * integer (as a 16-byte string) provided
     *
     * @param non-empty-string $bytes A 128-bit integer (16-byte string) to
     *     which the RFC 4122 version number and variant field will be applied,
     *     making the number a valid UUID
     * @param Version | null $version The RFC 4122 version to apply
     * @param Variant $variant The variant to apply
     *
     * @return non-empty-string A 16-byte string with the UUID version and variant applied
     *
     * @psalm-pure
     */
    public static function applyVersionAndVariant(
        string $bytes,
        ?Version $version,
        Variant $variant = Variant::Rfc4122,
    ): string {
        if (strlen($bytes) !== 16) {
            throw new InvalidArgumentException('$bytes must be a a 16-byte string');
        }

        /** @var int[] $parts */
        $parts = unpack('n*', $bytes);

        if ($version !== null) {
            $parts[4] = self::applyVersion($parts[4], $version);
        }

        $parts[5] = self::applyVariant($parts[5], $variant);

        /** @var non-empty-string */
        return pack('n*', ...$parts);
    }

    /**
     * Returns an 8-byte string representing a count of 100-nanosecond intervals
     * since the Gregorian epoch, 1582-10-15 00:00:00
     *
     * @param DateTimeInterface $dateTime The date-time for which to construct
     *     a count of 100-nanosecond intervals since the Gregorian epoch
     *
     * @return non-empty-string
     */
    public static function getTimeBytesForGregorianEpoch(DateTimeInterface $dateTime): string
    {
        if (PHP_INT_SIZE >= 8) {
            /** @var non-empty-string */
            return pack('J*', (int) $dateTime->format('Uu0') + self::GREGORIAN_OFFSET_INT);
        }

        /** @var non-empty-string */
        return str_pad(
            BigInteger::of($dateTime->format('Uu0'))
                ->plus(BigInteger::fromBytes(self::GREGORIAN_OFFSET_BIN))
                ->toBytes(false),
            8,
            "\x00",
            STR_PAD_LEFT,
        );
    }

    /**
     * Returns a 6-byte string representing the number of milliseconds since the
     * Unix Epoch, 1970-01-01 00:00:00
     *
     * @param DateTimeInterface $dateTime The date-time for which to construct
     *     a count of milliseconds since the Unix Epoch
     *
     * @return non-empty-string
     */
    public static function getTimeBytesForUnixEpoch(DateTimeInterface $dateTime): string
    {
        if (PHP_INT_SIZE >= 8) {
            /** @var non-empty-string */
            return substr(pack('J*', intdiv((int) $dateTime->format('Uu'), 1000)), 2);
        }

        /** @var non-empty-string */
        return str_pad(
            BigInteger::of($dateTime->format('Uu'))
                ->dividedBy(1000, RoundingMode::DOWN)
                ->toBytes(false),
            6,
            "\x00",
            STR_PAD_LEFT,
        );
    }

    /**
     * Applies the RFC 4122 variant field to the 16-bit clock sequence
     *
     * @link http://tools.ietf.org/html/rfc4122#section-4.1.1 RFC 4122, § 4.1.1: Variant
     *
     * @param int $clockSeq The 16-bit clock sequence value before the RFC 4122
     *     variant is applied
     *
     * @return int The 16-bit clock sequence multiplexed with the UUID variant
     *
     * @psalm-pure
     */
    private static function applyVariant(int $clockSeq, Variant $variant): int
    {
        return match ($variant) {
            Variant::ReservedNcs => $clockSeq & 0x7fff,
            Variant::Rfc4122 => $clockSeq & 0x3fff | 0x8000,
            Variant::ReservedMicrosoft => $clockSeq & 0x1fff | 0xc000,
            Variant::ReservedFuture => $clockSeq & 0x1fff | 0xe000,
        };
    }

    /**
     * Applies the RFC 4122 version number to the 16-bit `time_hi_and_version` field
     *
     * @link http://tools.ietf.org/html/rfc4122#section-4.1.3 RFC 4122, § 4.1.3: Version
     *
     * @param int $timeHi The value of the 16-bit `time_hi_and_version` field
     *     before the RFC 4122 version is applied
     * @param Version $version The RFC 4122 version to apply to the `time_hi` field
     *
     * @return int The 16-bit time_hi field of the timestamp multiplexed with
     *     the UUID version number
     *
     * @psalm-pure
     */
    private static function applyVersion(int $timeHi, Version $version): int
    {
        $timeHi = $timeHi & 0x0fff;
        $timeHi |= $version->value << 12;

        return $timeHi;
    }

    /**
     * Disallow public instantiation
     */
    private function __construct()
    {
    }
}
