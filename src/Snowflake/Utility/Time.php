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
use Ramsey\Identifier\Service\Os\Os;
use Ramsey\Identifier\Service\Os\PhpOs;
use Ramsey\Identifier\Snowflake;

use function sprintf;
use function substr;

/**
 * @internal
 */
final class Time
{
    private readonly bool $is64Bit;

    public function __construct(private readonly Os $os = new PhpOs())
    {
        $this->is64Bit = $this->os->getIntSize() >= 8;
    }

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
    ): DateTimeImmutable {
        if ($this->is64Bit) {
            // Timestamp should be at least 4 characters for "division" to work.
            $milliseconds = sprintf('%04s', ($snowflake->toInteger() >> 22) + $epochOffset);

            // Native division by 1_000 is faster, but it can cause precision
            // headaches, so we'll manually "divide" by inserting a decimal.
            $timestamp = substr($milliseconds, 0, -3) . '.' . substr($milliseconds, -3);
        } else {
            $timestamp = (string) BigInteger::of($snowflake->toString())
                ->shiftedRight(22)
                ->plus($epochOffset)
                ->toBigDecimal()
                ->dividedBy(
                    1000,
                    3,
                    RoundingMode::HALF_UP,
                );
        }

        return new DateTimeImmutable('@' . $timestamp);
    }
}
