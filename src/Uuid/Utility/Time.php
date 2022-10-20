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
use DateTimeInterface;
use Ramsey\Identifier\Exception\InvalidArgument;

use function intdiv;
use function pack;
use function str_pad;
use function substr;

use const PHP_INT_SIZE;
use const STR_PAD_LEFT;

/**
 * @internal
 */
final class Time
{
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
     * The number of 100-nanosecond intervals in one second.
     */
    public const NANOSECOND_INTERVALS = 10000000;

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
        if ($dateTime->format('Y-m-d') < '1582-10-15') {
            throw new InvalidArgument('Unable to get bytes for a timestamp earlier than the Gregorian epoch');
        }

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
     *
     * @throws InvalidArgument
     */
    public function getTimeBytesForUnixEpoch(DateTimeInterface $dateTime): string
    {
        if ($dateTime->getTimestamp() < 0) {
            throw new InvalidArgument('Unable to get bytes for a timestamp earlier than the Unix Epoch');
        }

        if (PHP_INT_SIZE >= 8) {
            /** @var non-empty-string */
            return substr(pack('J*', intdiv((int) $dateTime->format('Uu'), 1000)), -6);
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
}
