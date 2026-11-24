<?php

/**
 * This file is part of ramsey/identifier
 *
 * Copyright © Ben Ramsey <ben@ramsey.dev> and Contributors
 *
 * ramsey/identifier is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Lesser General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * ramsey/identifier is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with ramsey/identifier. If not, see <https://www.gnu.org/licenses/>.
 *
 * SPDX-License-Identifier: LGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Uuid\Internal;

use DateTimeImmutable;
use DateTimeInterface;
use Ramsey\Identifier\Exception\BadMethodCall;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\TimeBasedUuid;
use Ramsey\Identifier\Uuid\Version;

use function abs;
use function intdiv;
use function pack;
use function sprintf;
use function substr;
use function unpack;

/**
 * A utility for getting time values from UUIDs
 *
 * @internal Not intended for use outside ramsey/identifier; may change without
 *     notice.
 */
final class Time
{
    /**
     * The number of 100-nanosecond intervals between the Gregorian epoch and
     * the Unix epoch
     */
    private const GREGORIAN_OFFSET = 0x01b21dd213814000;

    /**
     * The maximum timestamp allowed in 100-nanosecond intervals since the
     * Gregorian epoch
     */
    private const MAX_GREGORIAN_TIME = 0x0fffffffffffffff;

    /**
     * The number of microseconds in one second
     */
    private const MICROSECONDS = 1_000_000;

    /**
     * The number of 100-nanosecond intervals in one second
     */
    private const INTERVALS_PER_SECOND = 10_000_000;

    /**
     * The number of 100-nanosecond intervals in one microsecond
     */
    private const INTERVALS_PER_MICROSECOND = 10;

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Returns a date-time instance created from the timestamp extracted from a
     * time-based UUID
     */
    public static function getDateTimeForUuid(TimeBasedUuid $uuid): DateTimeImmutable
    {
        return new DateTimeImmutable('@' . self::getTimestamp($uuid));
    }

    /**
     * Returns an 8-byte string representing a count of 100-nanosecond intervals
     * since the Gregorian epoch
     *
     * The Gregorian epoch starts at 1582-10-15 00:00:00.
     *
     * @param DateTimeInterface $dateTime The date-time for which to construct a
     *     count of 100-nanosecond intervals since the Gregorian epoch
     *
     * @return non-empty-string
     *
     * @throws InvalidArgument
     */
    public static function getTimeBytesForGregorianEpoch(DateTimeInterface $dateTime): string
    {
        $timestamp = $dateTime->getTimestamp();

        if ($timestamp < -intdiv(self::GREGORIAN_OFFSET, self::INTERVALS_PER_SECOND)) {
            throw new InvalidArgument('Unable to get bytes for a timestamp earlier than the Gregorian epoch');
        }

        // Test the range before multiplying, so the multiplication stays within
        // the range of an int.
        if ($timestamp > intdiv(self::MAX_GREGORIAN_TIME - self::GREGORIAN_OFFSET, self::INTERVALS_PER_SECOND)) {
            throw self::exceedsMaximum($dateTime);
        }

        // The timestamp is whole seconds, rounded down, and the fraction of a
        // second is never negative, so adding them counts the intervals right
        // on both sides of the Unix Epoch.
        $intervals = $timestamp * self::INTERVALS_PER_SECOND
            + (int) $dateTime->format('u') * self::INTERVALS_PER_MICROSECOND
            + self::GREGORIAN_OFFSET;

        if ($intervals > self::MAX_GREGORIAN_TIME) {
            throw self::exceedsMaximum($dateTime);
        }

        /** @var non-empty-string */
        return pack('J', $intervals);
    }

    private static function exceedsMaximum(DateTimeInterface $dateTime): InvalidArgument
    {
        return new InvalidArgument(sprintf(
            'The date exceeds the maximum value allowed for Gregorian time UUIDs: %s',
            $dateTime->format('Y-m-d H:i:s.u P'),
        ));
    }

    /**
     * For time-based UUIDs, returns the Unix timestamp with microsecond
     * resolution as a string
     *
     * @return numeric-string
     */
    private static function getTimestamp(TimeBasedUuid $uuid): string
    {
        $bytes = Binary::getNetworkBytes($uuid);

        return match ($uuid->getVersion()) {
            Version::GregorianTime => self::getTimestampGregorian($bytes),
            Version::DceSecurity => self::getTimestampDceSecurity($bytes),
            Version::ReorderedGregorianTime => self::getTimestampReorderedGregorian($bytes),
            Version::UnixTime => self::getTimestampUnix($bytes),
            default => throw new BadMethodCall('method called out of context'),
        };
    }

    /**
     * @param non-empty-string $bytes
     *
     * @return numeric-string
     */
    private static function getTimestampGregorian(string $bytes): string
    {
        /** @var int[] $parts */
        $parts = unpack('n4', substr($bytes, 0, 8));

        return self::divideTimestampGregorian(
            ($parts[4] & 0x0fff) << 48 | $parts[3] << 32 | $parts[1] << 16 | $parts[2],
        );
    }

    /**
     * @param non-empty-string $bytes
     *
     * @return numeric-string
     */
    private static function getTimestampDceSecurity(string $bytes): string
    {
        /** @var int[] $parts */
        $parts = unpack('n4', substr($bytes, 0, 8));

        return self::divideTimestampGregorian(
            ($parts[4] & 0x0fff) << 48 | $parts[3] << 32,
        );
    }

    /**
     * @param non-empty-string $bytes
     *
     * @return numeric-string
     */
    private static function getTimestampReorderedGregorian(string $bytes): string
    {
        /** @var int[] $parts */
        $parts = unpack('n4', substr($bytes, 0, 8));

        return self::divideTimestampGregorian(
            $parts[1] << 44 | $parts[2] << 28 | $parts[3] << 12 | $parts[4] & 0x0fff,
        );
    }

    /**
     * @param non-empty-string $bytes
     *
     * @return numeric-string
     */
    private static function getTimestampUnix(string $bytes): string
    {
        /** @var int[] $parts */
        $parts = unpack('J', "\x00\x00" . substr($bytes, 0, 6));

        /** @var numeric-string */
        return sprintf('%d.%03d', intdiv($parts[1], 1000), abs($parts[1]) % 1000);
    }

    /**
     * Converts a count of 100-nanosecond intervals from the Gregorian epoch
     * to a Unix timestamp in seconds, as a string with six decimal places
     *
     * The whole seconds are rounded down, and the rest of the time is rounded
     * to the nearest microsecond, with a half rounded up.
     *
     * @return numeric-string
     */
    private static function divideTimestampGregorian(int $timestamp): string
    {
        $intervals = $timestamp - self::GREGORIAN_OFFSET;

        // Round the seconds down, so the remainder is never negative.
        $seconds = intdiv($intervals, self::INTERVALS_PER_SECOND);
        $remainder = $intervals % self::INTERVALS_PER_SECOND;

        if ($remainder < 0) {
            $seconds--;
            $remainder += self::INTERVALS_PER_SECOND;
        }

        // Round the remainder to the nearest microsecond, with halves rounded
        // up. A remainder that rounds up to a whole second adds to the seconds
        // below.
        $microseconds = $seconds * self::MICROSECONDS + intdiv(
            $remainder + intdiv(self::INTERVALS_PER_MICROSECOND, 2),
            self::INTERVALS_PER_MICROSECOND,
        );

        // The sign is written apart from the digits because a timestamp after
        // "@" is a sign and a magnitude, so a time less than one second before
        // the Unix Epoch, which has zero whole seconds, keeps its sign.

        /** @var numeric-string */
        return sprintf(
            '%s%d.%06d',
            $microseconds < 0 ? '-' : '',
            abs(intdiv($microseconds, self::MICROSECONDS)),
            abs($microseconds % self::MICROSECONDS),
        );
    }
}
