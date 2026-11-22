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

namespace Ramsey\Identifier\Snowflake;

use Brick\Math\BigInteger;
use DateTimeInterface;
use Psr\Clock\ClockInterface as Clock;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Clock\ClockSequence;
use Ramsey\Identifier\Service\Clock\MonotonicClockSequence;
use Ramsey\Identifier\Service\Clock\SystemClock;
use Ramsey\Identifier\Snowflake;
use Ramsey\Identifier\Snowflake\Internal\StandardFactory;
use Ramsey\Identifier\Snowflake\Internal\Time;
use Ramsey\Identifier\SnowflakeFactory;

use function sprintf;

/**
 * A factory that generates generic Snowflakes identifiers that may use any
 * epoch offset
 *
 * @see GenericSnowflake
 */
final class GenericSnowflakeFactory implements SnowflakeFactory
{
    use StandardFactory;

    private const TIMESTAMP_BIT_SHIFTS = 22;

    /**
     * We increase this value each time our clock sequence rolls over and add
     * the value to the milliseconds to ensure the values are monotonically
     * increasing
     */
    private int $clockSequenceCounter = 0;

    /**
     * For performance, we'll prepare the node ID bits and store for later use
     */
    private readonly int $nodeIdShifted;

    private readonly int $epochOffset;

    /**
     * @param int $nodeId A node identifier to use when creating Snowflakes; we
     *     take the modulo of this integer divided by 1024, giving it an
     *     effective range of 0-1023 (i.e., 10 bits)
     * @param Epoch | int $epochOffset The offset from the Unix Epoch in
     *     milliseconds to use when creating Snowflake identifiers
     * @param Clock $clock A clock used to provide a date-time instance;
     *     defaults to {@see SystemClock}
     * @param ClockSequence $sequence A clock sequence value to prevent
     *     collisions; defaults to {@see MonotonicClockSequence}
     */
    public function __construct(
        private readonly int $nodeId,
        Epoch | int $epochOffset,
        private readonly Clock $clock = new SystemClock(),
        private readonly ClockSequence $sequence = new MonotonicClockSequence(),
    ) {
        // Use modular arithmetic to roll over the node value at mod 0x0400
        // (1024).
        $this->nodeIdShifted = $this->nodeId % 0x0400 << 12;
        $this->epochOffset = $epochOffset instanceof Epoch ? $epochOffset->value : $epochOffset;
    }

    /**
     * @throws InvalidArgument
     */
    public function create(): Snowflake
    {
        return $this->createFromDateTime($this->clock->now());
    }

    /**
     * @param non-empty-string $identifier
     *
     * @throws InvalidArgument
     */
    public function createFromBytes(string $identifier): Snowflake
    {
        return new GenericSnowflake($this->convertFromBytes($identifier), $this->epochOffset);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromDateTime(DateTimeInterface $dateTime): Snowflake
    {
        $milliseconds = Time::getUnixMilliseconds($dateTime) - $this->epochOffset;
        $isExact = Time::hasExactUnixMilliseconds($dateTime);

        // A date-time too early to count exactly is earlier than every epoch
        // that can be counted exactly.
        if ($milliseconds < 0 && ($isExact || $this->epochOffset >= Time::MIN_EXACT_UNIX_MILLISECONDS)) {
            throw new InvalidArgument(sprintf(
                'Timestamp may not be earlier than the epoch, %s',
                Time::getDateTimeForUnixMilliseconds($this->epochOffset)->format(Epoch::ISO_EXTENDED_FORMAT),
            ));
        }

        if ($milliseconds > 0x3ffffffffff) {
            $maxDateTime = Time::getDateTimeForUnixMilliseconds(0x3ffffffffff + $this->epochOffset);

            throw new InvalidArgument(sprintf(
                'Snowflakes with epoch offset %d cannot have a date-time greater than %s',
                $this->epochOffset,
                $maxDateTime->format(Epoch::ISO_EXTENDED_FORMAT),
            ));
        }

        // The count of a date-time this far from the Unix Epoch is not its true
        // count, so it must not become the timestamp of a snowflake.
        if (!$isExact) {
            throw new InvalidArgument(sprintf(
                'The date-time %s is too far from the Unix Epoch to count in milliseconds',
                $dateTime->format(Epoch::ISO_EXTENDED_FORMAT),
            ));
        }

        // Use modular arithmetic to roll over the sequence value at mod 0x1000
        // (4096).
        $sequence = $this->sequence->next((string) $this->nodeId, $dateTime) % 0x1000;

        // Increase the milliseconds by the current value of the clock sequence
        // counter.
        $milliseconds += $this->clockSequenceCounter;
        $millisecondsShifted = $milliseconds << self::TIMESTAMP_BIT_SHIFTS;

        // If the sequence is currently 0x0fff (4095), bump the clock sequence
        // counter, since we're rolling over.
        if ($sequence === 0x0fff) {
            $this->clockSequenceCounter++;
        }

        if ($millisecondsShifted > $milliseconds) {
            /** @var int<0, max> $identifier */
            $identifier = $millisecondsShifted | $this->nodeIdShifted | $sequence;
        } else {
            /** @var non-empty-string & numeric-string $identifier */
            $identifier = (string) BigInteger::of($milliseconds)
                ->shiftedLeft(self::TIMESTAMP_BIT_SHIFTS)
                ->or($this->nodeIdShifted)
                ->or($sequence);
        }

        return new GenericSnowflake($identifier, $this->epochOffset);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromHexadecimal(string $identifier): Snowflake
    {
        return new GenericSnowflake($this->convertFromHexadecimal($identifier), $this->epochOffset);
    }

    /**
     * @param int<0, max> | numeric-string $identifier
     *
     * @throws InvalidArgument
     */
    public function createFromInteger(int | string $identifier): Snowflake
    {
        return new GenericSnowflake($identifier, $this->epochOffset);
    }

    /**
     * @param numeric-string $identifier
     *
     * @throws InvalidArgument
     */
    public function createFromString(string $identifier): Snowflake
    {
        return new GenericSnowflake($identifier, $this->epochOffset);
    }
}
