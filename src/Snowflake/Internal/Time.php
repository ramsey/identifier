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

namespace Ramsey\Identifier\Snowflake\Internal;

use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use DateTimeImmutable;
use DateTimeInterface;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotConvertible;
use Ramsey\Identifier\Snowflake;

use function abs;
use function intdiv;
use function is_int;
use function sprintf;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * A utility for getting the date-time from a Snowflake identifier
 *
 * @internal Not intended for use outside ramsey/identifier; may change without
 *     notice.
 */
final class Time
{
    private const SNOWFLAKE_BITS = 64;

    /**
     * The number of milliseconds in one second
     */
    private const MILLISECONDS = 1000;

    private function __construct()
    {
    }

    /**
     * Returns a date-time instance created from the timestamp extracted from a
     * Snowflake
     *
     * @param int $epochOffset The number of milliseconds from the Unix Epoch to
     *     offset the starting epoch for this Snowflake
     *
     * @throws InvalidArgument when the number of right shifts is negative
     * @throws NotConvertible when the date-time of the Snowflake cannot be
     *     represented
     */
    public static function getDateTimeForSnowflake(
        Snowflake $snowflake,
        int $epochOffset,
        int $rightShifts,
    ): DateTimeImmutable {
        if ($rightShifts < 0) {
            throw new InvalidArgument(sprintf(
                'The number of right shifts must not be negative; received %d',
                $rightShifts,
            ));
        }

        // A snowflake has at most 64 bits, so shifting it right by 64 or more
        // bits leaves zero.
        if ($rightShifts >= self::SNOWFLAKE_BITS) {
            $value = 0;
            $rightShifts = 0;
        } else {
            $value = $snowflake->toInteger();
        }

        // We support unsigned, 64-bit integers, so $value might be greater than
        // PHP_INT_MAX, in which case, it'll be a string, and we'll need to use
        // BigInteger for the math.
        if (is_int($value)) {
            $shifted = $value >> $rightShifts;

            if ($epochOffset > 0 && $shifted > PHP_INT_MAX - $epochOffset) {
                throw new NotConvertible('The date-time of the snowflake cannot be represented');
            }

            return self::getDateTimeForUnixMilliseconds($shifted + $epochOffset);
        }

        $timestamp = (string) BigInteger::of($value)
            ->shiftedRight($rightShifts)
            ->plus($epochOffset)
            ->toBigDecimal()
            ->dividedBy(1000, 3, RoundingMode::HalfUp);

        return new DateTimeImmutable('@' . $timestamp);
    }

    /**
     * Returns the date-time that is the given number of milliseconds after the
     * Unix Epoch, or before it when the number is negative
     */
    public static function getDateTimeForUnixMilliseconds(int $milliseconds): DateTimeImmutable
    {
        // The sign is written apart from the digits because a time less than
        // one second before the Unix Epoch has zero whole seconds and would
        // lose its sign.
        return new DateTimeImmutable(sprintf(
            '@%s%d.%03d',
            $milliseconds < 0 ? '-' : '',
            abs(intdiv($milliseconds, self::MILLISECONDS)),
            abs($milliseconds % self::MILLISECONDS),
        ));
    }

    /**
     * Returns the number of milliseconds from the Unix Epoch to the date-time,
     * which is negative for a date-time before the Unix Epoch
     *
     * A date-time too far from the Unix Epoch to count in milliseconds as an
     * int returns the largest or the smallest int. So does a date-time in the
     * last second that an int can count.
     */
    public static function getUnixMilliseconds(DateTimeInterface $dateTime): int
    {
        $timestamp = $dateTime->getTimestamp();

        if ($timestamp > intdiv(PHP_INT_MAX, self::MILLISECONDS) - 1) {
            return PHP_INT_MAX;
        }

        if ($timestamp < intdiv(PHP_INT_MIN, self::MILLISECONDS)) {
            return PHP_INT_MIN;
        }

        // The timestamp is whole seconds, rounded down, and the fraction of a
        // second is never negative, so adding them gives the right number of
        // milliseconds on both sides of the Unix Epoch.
        return $timestamp * self::MILLISECONDS + (int) $dateTime->format('v');
    }
}
