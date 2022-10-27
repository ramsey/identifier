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

namespace Ramsey\Identifier\Uuid\Utility;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use DateTimeImmutable;
use DateTimeInterface;
use Ramsey\Identifier\Exception\BadMethodCall;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Os\Os;
use Ramsey\Identifier\Service\Os\PhpOs;
use Ramsey\Identifier\TimeBasedUuid;
use Ramsey\Identifier\Uuid\MicrosoftGuid;
use Ramsey\Identifier\Uuid\Version;

use function hex2bin;
use function pack;
use function sprintf;
use function str_pad;
use function substr;
use function unpack;

use const STR_PAD_LEFT;

/**
 * @internal
 */
final class Time
{
    /**
     * The 100-nanosecond interval count of the Gregorian Epoch, relative to
     * the Unix Epoch. This is stored as a string, since we use it only for
     * string comparison.
     *
     * Derived from:
     *
     * ```php
     * (new DateTimeImmutable('1582-10-15 00:00:00.0'))->format('Uu0');
     * ```
     */
    private const GREGORIAN_EPOCH = '-122192928000000000';

    /**
     * The number of 100-nanosecond intervals between the Gregorian epoch and
     * the Unix epoch, as a hexadecimal string for 32-bit systems
     */
    private const GREGORIAN_OFFSET_HEX = '01b21dd213814000';

    /**
     * The number of 100-nanosecond intervals between the Gregorian epoch and
     * the Unix epoch, as a native integer
     */
    private const GREGORIAN_OFFSET_INT = 0x01b21dd213814000;

    /**
     * The number of milliseconds in one second
     */
    private const MILLISECONDS = 1_000;

    /**
     * The number of 100-nanosecond intervals in one second.
     */
    private const NANOSECOND_INTERVALS = 10_000_000;

    /**
     * The integer size on this system (useful for determining whether
     * we can use 64-bit numbers).
     */
    private readonly int $intSize;

    public function __construct(Os $os = new PhpOs())
    {
        $this->intSize = $os->getIntSize();
    }

    /**
     * Returns a date-time instance created from the timestamp extracted from
     * a time-based UUID
     */
    public function getDateTimeForUuid(TimeBasedUuid $uuid): DateTimeImmutable
    {
        if ($this->intSize >= 8) {
            $timestamp = $this->getTimestamp($uuid);
        } else {
            $timestamp = $this->getTimestamp32Bit($uuid);
        }

        return new DateTimeImmutable('@' . $timestamp);
    }

    /**
     * Returns an 8-byte string representing a count of 100-nanosecond intervals
     * since the Gregorian epoch, 1582-10-15 00:00:00
     *
     * @param DateTimeInterface $dateTime The date-time for which to construct
     *     a count of 100-nanosecond intervals since the Gregorian epoch
     *
     * @return non-empty-string
     *
     * @throws InvalidArgument
     */
    public function getTimeBytesForGregorianEpoch(DateTimeInterface $dateTime): string
    {
        // A count of 100-nanosecond intervals
        $intervals = $dateTime->format('Uu0');

        if ($intervals < self::GREGORIAN_EPOCH) {
            throw new InvalidArgument('Unable to get bytes for a timestamp earlier than the Gregorian epoch');
        }

        if ($this->intSize >= 8) {
            /** @var non-empty-string */
            return pack('J', (int) $intervals + self::GREGORIAN_OFFSET_INT);
        }

        $bytes = BigInteger::of($intervals)
            ->plus(BigInteger::fromBase(self::GREGORIAN_OFFSET_HEX, 16))
            ->toBytes(false);

        /** @var non-empty-string */
        return str_pad($bytes, 8, "\x00", STR_PAD_LEFT);
    }

    /**
     * For time-based UUIDs, returns the Unix timestamp with microsecond
     * resolution as a string
     */
    private function getTimestamp(TimeBasedUuid $uuid): string
    {
        if ($uuid instanceof MicrosoftGuid) {
            // Convert the hexadecimal representation to bytes, since the
            // hexadecimal representation of a GUID already has the bytes swapped.
            $bytes = (string) hex2bin($uuid->toHexadecimal());
        } else {
            $bytes = $uuid->toBytes();
        }

        return match ($uuid->getVersion()) {
            Version::GregorianTime => $this->getTimestampGregorian($bytes),
            Version::DceSecurity => $this->getTimestampDceSecurity($bytes),
            Version::ReorderedGregorianTime => $this->getTimestampReorderedGregorian($bytes),
            Version::UnixTime => $this->getTimestampUnix($bytes),
            default => throw new BadMethodCall('method called out of context'), // @codeCoverageIgnore
        };
    }

    private function getTimestampGregorian(string $bytes): string
    {
        /** @var int[] $parts */
        $parts = unpack('n4', substr($bytes, 0, 8));

        return $this->divideTimestampGregorian(
            ($parts[4] & 0x0fff) << 48 | $parts[3] << 32 | $parts[1] << 16 | $parts[2],
        );
    }

    private function getTimestampDceSecurity(string $bytes): string
    {
        /** @var int[] $parts */
        $parts = unpack('n4', substr($bytes, 0, 8));

        return $this->divideTimestampGregorian(
            ($parts[4] & 0x0fff) << 48 | $parts[3] << 32,
        );
    }

    private function getTimestampReorderedGregorian(string $bytes): string
    {
        /** @var int[] $parts */
        $parts = unpack('n4', substr($bytes, 0, 8));

        return $this->divideTimestampGregorian(
            $parts[1] << 44 | $parts[2] << 28 | $parts[3] << 12 | $parts[4] & 0x0fff,
        );
    }

    private function getTimestampUnix(string $bytes): string
    {
        /** @var int[] $parts */
        $parts = unpack('J', "\x00\x00" . substr($bytes, 0, 6));

        // Timestamp should be at least 4 characters for "division" to work.
        $timestamp = sprintf('%04s', $parts[1]);

        // Native division by 1_000 is faster, but it can cause precision
        // headaches, so we'll manually "divide" by inserting a decimal.
        return substr($timestamp, 0, -3) . '.' . substr($timestamp, -3);
    }

    /**
     * Divides the Gregorian timestamp by 100-nanosecond intervals
     * (i.e., 10_000_000) and returns the timestamp as a string with microsecond
     * precision (i.e., 6).
     *
     * Native division by 10_000_000 is faster, but it can cause precision
     * headaches, so we'll cut off the trailing digit, use it to manually
     * round the timestamp with "half up" logic, and insert the decimal in
     * the correct position.
     */
    private function divideTimestampGregorian(int $timestamp): string
    {
        $timestamp -= self::GREGORIAN_OFFSET_INT;
        $roundingDigit = (int) substr((string) $timestamp, -1);
        $timestamp = (int) substr((string) $timestamp, 0, -1);

        if ($roundingDigit >= 5) {
            $timestamp++;
        }

        // Timestamp should be at least 7 characters for "division" to work.
        $timestamp = sprintf('%07s', $timestamp);

        return substr($timestamp, 0, -6) . '.' . substr($timestamp, -6);
    }

    /**
     * For time-based UUIDs, returns the Unix timestamp with microsecond
     * resolution as a string (for use on 32-bit systems)
     */
    private function getTimestamp32Bit(TimeBasedUuid $uuid): string
    {
        return match ($uuid->getVersion()) {
            Version::GregorianTime => $this->getTimestampGregorian32Bit($uuid->toHexadecimal()),
            Version::DceSecurity => $this->getTimestampDceSecurity32Bit($uuid->toHexadecimal()),
            Version::ReorderedGregorianTime => $this->getTimestampReorderedGregorian32Bit($uuid->toHexadecimal()),
            Version::UnixTime => $this->getTimestampUnix32Bit($uuid->toHexadecimal()),
            default => throw new BadMethodCall('method called out of context'), // @codeCoverageIgnore
        };
    }

    private function getTimestampGregorian32Bit(string $hexadecimal): string
    {
        $hexadecimal = substr($hexadecimal, 13, 3)
            . substr($hexadecimal, 8, 4)
            . substr($hexadecimal, 0, 8);

        return (string) $this->divideTimestampGregorian32Bit($hexadecimal);
    }

    private function getTimestampDceSecurity32Bit(string $hexadecimal): string
    {
        $hexadecimal = substr($hexadecimal, 13, 3)
            . substr($hexadecimal, 8, 4)
            . '00000000';

        return (string) $this->divideTimestampGregorian32Bit($hexadecimal);
    }

    private function getTimestampReorderedGregorian32Bit(string $hexadecimal): string
    {
        $hexadecimal = '0' . substr($hexadecimal, 0, 12)
            . substr($hexadecimal, 13, 3);

        return (string) $this->divideTimestampGregorian32Bit($hexadecimal);
    }

    private function getTimestampUnix32Bit(string $hexadecimal): string
    {
        return (string) BigInteger::fromBase(substr($hexadecimal, 0, 12), 16)
            ->toBigDecimal()
            ->dividedBy(self::MILLISECONDS, 3, RoundingMode::HALF_UP);
    }

    private function divideTimestampGregorian32Bit(string $hexadecimal): BigDecimal
    {
        return BigInteger::fromBase($hexadecimal, 16)
            ->minus(BigInteger::fromBase(self::GREGORIAN_OFFSET_HEX, 16))
            ->toBigDecimal()
            ->dividedBy(self::NANOSECOND_INTERVALS, 6, RoundingMode::HALF_UP);
    }
}
