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

namespace Ramsey\Identifier\Snowflake\Internal;

use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use DateTimeImmutable;
use Identifier\Exception\OutOfRange;
use Ramsey\Identifier\Snowflake;

use function abs;
use function intdiv;
use function is_int;
use function sprintf;

/**
 * A utility for getting the date-time from a Snowflake identifier.
 *
 * @internal Not intended for use outside ramsey/identifier; may change without notice.
 */
final class Time
{
    /**
     * Returns a date-time instance created from the timestamp extracted from a Snowflake.
     *
     * @param int $epochOffset The number of milliseconds from the Unix Epoch to offset the starting epoch for this Snowflake.
     *
     * @throws OutOfRange
     */
    public function getDateTimeForSnowflake(
        Snowflake $snowflake,
        int $epochOffset,
        int $rightShifts,
    ): DateTimeImmutable {
        $value = $snowflake->toInteger();

        // We support unsigned, 64-bit integers, so $value might be greater than PHP_INT_MAX, in which case, it'll be a
        // string, and we'll need to use BigInteger for the math.
        if (is_int($value)) {
            $milliseconds = ($value >> $rightShifts) + $epochOffset;
            $timestamp = sprintf('%d.%03d', intdiv($milliseconds, 1000), abs($milliseconds) % 1000);
        } else {
            $timestamp = (string) BigInteger::of($value)
                ->shiftedRight($rightShifts)
                ->plus($epochOffset)
                ->toBigDecimal()
                ->dividedBy(1000, 3, RoundingMode::HALF_UP);
        }

        return new DateTimeImmutable('@' . $timestamp);
    }
}
