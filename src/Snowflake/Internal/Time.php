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
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotConvertible;
use Ramsey\Identifier\Snowflake;

use function abs;
use function intdiv;
use function is_int;
use function sprintf;

use const PHP_INT_MAX;

/**
 * A utility for getting the date-time from a Snowflake identifier
 *
 * @internal Not intended for use outside ramsey/identifier; may change without
 *     notice.
 */
final class Time
{
    private const SNOWFLAKE_BITS = 64;

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

            $milliseconds = $shifted + $epochOffset;
            $timestamp = sprintf(
                '%s%d.%03d',
                $milliseconds < 0 ? '-' : '',
                abs(intdiv($milliseconds, 1000)),
                abs($milliseconds % 1000),
            );
        } else {
            $timestamp = (string) BigInteger::of($value)
                ->shiftedRight($rightShifts)
                ->plus($epochOffset)
                ->toBigDecimal()
                ->dividedBy(1000, 3, RoundingMode::HalfUp);
        }

        return new DateTimeImmutable('@' . $timestamp);
    }
}
