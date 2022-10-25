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

use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use DateTimeImmutable;
use Ramsey\Identifier\Exception\BadMethodCall;
use Ramsey\Identifier\Uuid\Version;

use function hexdec;
use function sprintf;
use function substr;

/**
 * This internal trait provides functionality common to time-based UUIDs
 *
 * @internal
 *
 * @psalm-immutable
 */
trait TimeBasedUuid
{
    use StandardUuid;

    public function getDateTime(): DateTimeImmutable
    {
        return match ($this->getVersion()) {
            Version::GregorianTime, Version::DceSecurity,
            Version::ReorderedGregorianTime => $this->getDateTimeGregorian(),
            Version::UnixTime => $this->getDateTimeUnix(),
            default => throw new BadMethodCall('getDateTime() called out of context'), // @codeCoverageIgnore
        };
    }

    private function getDateTimeGregorian(): DateTimeImmutable
    {
        /** @psalm-suppress ImpureMethodCall */
        $epochNanoseconds = BigInteger::fromBase($this->getTimestamp(), 16)
            ->minus(BigInteger::fromBytes(Time::GREGORIAN_OFFSET_BIN));

        $unixTimestamp = $epochNanoseconds->toBigDecimal()->dividedBy(
            Time::NANOSECOND_INTERVALS,
            6,
            RoundingMode::HALF_UP,
        );

        return new DateTimeImmutable('@' . $unixTimestamp);
    }

    private function getDateTimeUnix(): DateTimeImmutable
    {
        /** @psalm-suppress ImpureMethodCall */
        $epochMilliseconds = BigInteger::fromBase($this->getTimestamp(), 16);

        $unixTimestamp = $epochMilliseconds->toBigDecimal()->dividedBy(
            1000,
            3,
            RoundingMode::HALF_UP,
        );

        return new DateTimeImmutable('@' . $unixTimestamp);
    }

    /**
     * Returns the timestamp from the UUID as a hexadecimal string
     */
    private function getTimestamp(): string
    {
        return match ($this->getVersion()) {
            Version::GregorianTime => $this->getTimestampGregorian(),
            Version::DceSecurity => $this->getTimestampDceSecurity(),
            Version::ReorderedGregorianTime => $this->getTimestampReorderedTime(),
            Version::UnixTime => $this->getTimestampUnix(),
            default => throw new BadMethodCall('getTimestamp() called out of context'), // @codeCoverageIgnore
        };
    }

    /**
     * For version 1 UUIDs, returns the full 60-bit timestamp as a hexadecimal
     * string, without the version
     */
    private function getTimestampGregorian(): string
    {
        $uuid = $this->getFormat(Format::FORMAT_STRING);

        return sprintf(
            '%03x%04s%08s',
            hexdec(substr($uuid, 14, 4)) & 0x0fff,
            substr($uuid, 9, 4),
            substr($uuid, 0, 8),
        );
    }

    /**
     * For version 2 UUIDs, returns the full 60-bit timestamp as a hexadecimal
     * string, without the version
     *
     * For version 2 UUIDs, the time_low field is the local identifier and
     * should not be returned as part of the time. For this reason, we set the
     * bottom 32 bits of the timestamp to 0's. As a result, there is some loss
     * of fidelity of the timestamp, for version 2 UUIDs. The timestamp can be
     * off by a range of 0 to 429.4967295 seconds (or 7 minutes, 9 seconds, and
     * 496730 microseconds).
     */
    private function getTimestampDceSecurity(): string
    {
        $uuid = $this->getFormat(Format::FORMAT_STRING);

        return sprintf(
            '%03x%04s%08s',
            hexdec(substr($uuid, 14, 4)) & 0x0fff,
            substr($uuid, 9, 4),
            '',
        );
    }

    /**
     * For version 6 UUIDs, returns the full 60-bit timestamp as a hexadecimal
     * string, without the version
     *
     * For version 6 UUIDs, the timestamp order is reversed from the typical RFC
     * 4122 order (the time bits are in the correct bit order, so that it is
     * monotonically increasing). In returning the timestamp value, we put the
     * bits in the order: time_low + time_mid + time_hi.
     */
    private function getTimestampReorderedTime(): string
    {
        $uuid = $this->getFormat(Format::FORMAT_STRING);

        return sprintf(
            '%08s%04s%03x',
            substr($uuid, 0, 8),
            substr($uuid, 9, 4),
            hexdec(substr($uuid, 14, 4)) & 0x0fff,
        );
    }

    /**
     * For version 7 UUIDs, returns a 48-bit timestamp as a hexadecimal string
     * representing the Unix Epoch in milliseconds
     */
    protected function getTimestampUnix(): string
    {
        $uuid = $this->getFormat(Format::FORMAT_STRING);

        return sprintf('%08s%04s', substr($uuid, 0, 8), substr($uuid, 9, 4));
    }
}
