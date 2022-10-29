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

namespace Ramsey\Identifier\Snowflake\Utility;

use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use DateTimeImmutable;
use Ramsey\Identifier\Snowflake;

use function abs;
use function intdiv;
use function is_int;
use function sprintf;

/**
 * @internal
 */
final class Time
{
    /**
     * Returns a date-time instance created from the timestamp extracted from
     * a Snowflake
     *
     * @param int | numeric-string $epochOffset The number of milliseconds from
     *     the Unix Epoch to offset the starting epoch for this Snowflake
     */
    public function getDateTimeForSnowflake(
        Snowflake $snowflake,
        int | string $epochOffset,
        int $rightShifts,
    ): DateTimeImmutable {
        $value = $snowflake->toInteger();

        // We support unsigned, 64-bit integers, so $value might be greater than
        // PHP_INT_MAX, in which case, it'll be a string, and we'll need to use
        // BigInteger for the math.
        if (is_int($value)) {
            $milliseconds = (int) (($value >> $rightShifts) + $epochOffset);

            $timestamp = sprintf(
                '%d.%03d',
                intdiv($milliseconds, 1000),
                abs($milliseconds) % 1000,
            );
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
