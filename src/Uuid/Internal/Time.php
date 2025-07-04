<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser
 * General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * ramsey/identifier is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with ramsey/identifier. If not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@ramsey.dev> and Contributors
 * @license https://opensource.org/license/lgpl-3-0/ GNU Lesser General Public License version 3 or later
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Uuid\Internal;

use DateTimeImmutable;
use DateTimeInterface;
use Ramsey\Identifier\Exception\BadMethodCall;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\TimeBasedUuid;
use Ramsey\Identifier\Uuid\MicrosoftGuid;
use Ramsey\Identifier\Uuid\Version;

use function abs;
use function hex2bin;
use function intdiv;
use function pack;
use function sprintf;
use function substr;
use function unpack;

/**
 * A utility for getting time values from UUIDs.
 *
 * @internal Not intended for use outside ramsey/identifier; may change without notice.
 */
final class Time
{
    /**
     * The number of 100-nanosecond intervals between the Gregorian epoch and the Unix epoch.
     */
    private const GREGORIAN_OFFSET = 0x01b21dd213814000;

    /**
     * The maximum timestamp allowed in 100-nanosecond intervals since the Gregorian epoch.
     */
    private const MAX_GREGORIAN_TIME = 0x0fffffffffffffff;

    /**
     * The number of microseconds in one second.
     */
    private const MICROSECONDS = 1_000_000;

    /**
     * Returns a date-time instance created from the timestamp extracted from a time-based UUID.
     */
    public function getDateTimeForUuid(TimeBasedUuid $uuid): DateTimeImmutable
    {
        return new DateTimeImmutable('@' . $this->getTimestamp($uuid));
    }

    /**
     * Returns an 8-byte string representing a count of 100-nanosecond intervals since the Gregorian epoch.
     *
     * The Gregorian epoch starts at 1582-10-15 00:00:00.
     *
     * @param DateTimeInterface $dateTime The date-time for which to construct a count of 100-nanosecond intervals since
     *     the Gregorian epoch.
     *
     * @return non-empty-string
     *
     * @throws InvalidArgument
     */
    public function getTimeBytesForGregorianEpoch(DateTimeInterface $dateTime): string
    {
        // A count of 100-nanosecond intervals
        $intervals = (int) $dateTime->format('Uu0');

        if ($intervals < 0 - self::GREGORIAN_OFFSET) {
            throw new InvalidArgument('Unable to get bytes for a timestamp earlier than the Gregorian epoch');
        }

        $intervals = $intervals + self::GREGORIAN_OFFSET;

        if ($intervals > self::MAX_GREGORIAN_TIME) {
            throw new InvalidArgument(sprintf(
                'The date exceeds the maximum value allowed for Gregorian time UUIDs: %s',
                $dateTime->format('Y-m-d H:i:s.u P'),
            ));
        }

        /** @var non-empty-string */
        return pack('J', $intervals);
    }

    /**
     * For time-based UUIDs, returns the Unix timestamp with microsecond resolution as a string.
     *
     * @return numeric-string
     */
    private function getTimestamp(TimeBasedUuid $uuid): string
    {
        if ($uuid instanceof MicrosoftGuid) {
            // Convert the hexadecimal representation to bytes, since the hexadecimal representation of a GUID already
            // has the bytes swapped.
            /** @var non-empty-string $bytes */
            $bytes = (string) hex2bin($uuid->toHexadecimal());
        } else {
            $bytes = $uuid->toBytes();
        }

        return match ($uuid->getVersion()) {
            Version::GregorianTime => $this->getTimestampGregorian($bytes),
            Version::DceSecurity => $this->getTimestampDceSecurity($bytes),
            Version::ReorderedGregorianTime => $this->getTimestampReorderedGregorian($bytes),
            Version::UnixTime => $this->getTimestampUnix($bytes),
            default => throw new BadMethodCall('method called out of context'),
        };
    }

    /**
     * @param non-empty-string $bytes
     *
     * @return numeric-string
     */
    private function getTimestampGregorian(string $bytes): string
    {
        /** @var int[] $parts */
        $parts = unpack('n4', substr($bytes, 0, 8));

        return $this->divideTimestampGregorian(
            ($parts[4] & 0x0fff) << 48 | $parts[3] << 32 | $parts[1] << 16 | $parts[2],
        );
    }

    /**
     * @param non-empty-string $bytes
     *
     * @return numeric-string
     */
    private function getTimestampDceSecurity(string $bytes): string
    {
        /** @var int[] $parts */
        $parts = unpack('n4', substr($bytes, 0, 8));

        return $this->divideTimestampGregorian(
            ($parts[4] & 0x0fff) << 48 | $parts[3] << 32,
        );
    }

    /**
     * @param non-empty-string $bytes
     *
     * @return numeric-string
     */
    private function getTimestampReorderedGregorian(string $bytes): string
    {
        /** @var int[] $parts */
        $parts = unpack('n4', substr($bytes, 0, 8));

        return $this->divideTimestampGregorian(
            $parts[1] << 44 | $parts[2] << 28 | $parts[3] << 12 | $parts[4] & 0x0fff,
        );
    }

    /**
     * @param non-empty-string $bytes
     *
     * @return numeric-string
     */
    private function getTimestampUnix(string $bytes): string
    {
        /** @var int[] $parts */
        $parts = unpack('J', "\x00\x00" . substr($bytes, 0, 6));

        /** @var numeric-string */
        return sprintf('%d.%03d', intdiv($parts[1], 1000), abs($parts[1]) % 1000);
    }

    /**
     * Divides the Gregorian timestamp by 100-nanosecond intervals (i.e., 10_000_000) and returns the timestamp as a
     * string with microsecond precision (i.e., 6).
     *
     * We specifically do not do any rounding here, since we don't want the time to accidentally bump forward to the
     * next second.
     *
     * @return numeric-string
     */
    private function divideTimestampGregorian(int $timestamp): string
    {
        $timestamp -= self::GREGORIAN_OFFSET;

        // Convert time to microseconds from 100-nanosecond intervals.
        $timestamp = intdiv($timestamp, 10);

        /** @var numeric-string */
        return sprintf(
            '%d.%06d',
            intdiv($timestamp, self::MICROSECONDS),
            abs($timestamp) % self::MICROSECONDS,
        );
    }
}
