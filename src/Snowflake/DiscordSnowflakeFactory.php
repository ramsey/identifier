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

namespace Ramsey\Identifier\Snowflake;

use Brick\Math\BigInteger;
use DateTimeInterface;
use Psr\Clock\ClockInterface as Clock;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Clock\ClockSequence;
use Ramsey\Identifier\Service\Clock\MonotonicClockSequence;
use Ramsey\Identifier\Service\Clock\Precision;
use Ramsey\Identifier\Service\Clock\SystemClock;
use Ramsey\Identifier\Snowflake\Internal\StandardFactory;
use Ramsey\Identifier\SnowflakeFactory;

use function sprintf;

/**
 * A factory that generates Snowflake identifiers for use with the Discord voice, text, and streaming video platform.
 *
 * @link https://discord.com Discord.
 * @see DiscordSnowflake
 */
final class DiscordSnowflakeFactory implements SnowflakeFactory
{
    use StandardFactory;

    private const TIMESTAMP_BIT_SHIFTS = 22;

    /**
     * For performance, we'll prepare the worker and process ID bits and store them for repeated use.
     */
    private readonly int $workerProcessIdShifted;

    /**
     * We increase this value each time our clock sequence rolls over and add the value to the milliseconds to ensure
     * the values are monotonically increasing.
     */
    private int $clockSequenceCounter = 0;

    /**
     * @param int $workerId A worker identifier to use when creating Snowflakes; we take the modulo of this integer
     *     divided by 32, giving it an effective range of 0-31 (i.e., 5 bits).
     * @param int $processId A process identifier to use when creating Snowflakes; we take the modulo of this integer
     *     divided by 32, giving it an effective range of 0-31 (i.e., 5 bits).
     * @param Clock $clock A clock used to provide a date-time instance; defaults to {@see SystemClock}.
     * @param ClockSequence $sequence A clock sequence value to prevent collisions; defaults to {@see MonotonicClockSequence}.
     */
    public function __construct(
        private readonly int $workerId,
        private readonly int $processId,
        private readonly Clock $clock = new SystemClock(),
        private readonly ClockSequence $sequence = new MonotonicClockSequence(),
    ) {
        // Use modular arithmetic to roll over the worker and process IDs at mod 0x20 (32).
        $this->workerProcessIdShifted = $this->workerId % 0x20 << 17 | $this->processId % 0x20 << 12;
    }

    /**
     * @throws InvalidArgument
     */
    public function create(): DiscordSnowflake
    {
        return $this->createFromDateTime($this->clock->now());
    }

    /**
     * @param non-empty-string $identifier
     *
     * @throws InvalidArgument
     */
    public function createFromBytes(string $identifier): DiscordSnowflake
    {
        return new DiscordSnowflake($this->convertFromBytes($identifier));
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromDateTime(DateTimeInterface $dateTime): DiscordSnowflake
    {
        $milliseconds = (int) $dateTime->format(Precision::Millisecond->value) - Epoch::Discord->value;

        if ($milliseconds < 0) {
            throw new InvalidArgument(sprintf(
                'Timestamp may not be earlier than the Discord epoch, %s',
                Epoch::Discord->toIso8601(),
            ));
        }

        if ($milliseconds > 0x3ffffffffff) {
            throw new InvalidArgument(
                'Discord Snowflakes cannot have a date-time greater than 2154-05-15T07:35:11.103Z',
            );
        }

        // Use modular arithmetic to roll over the sequence value at mod 0x1000 (4096).
        $sequence = $this->sequence->next((string) ($this->workerId + $this->processId), $dateTime) % 0x1000;

        // Increase the milliseconds by the current value of the clock sequence counter.
        $milliseconds += $this->clockSequenceCounter;
        $millisecondsShifted = $milliseconds << self::TIMESTAMP_BIT_SHIFTS;

        // If the sequence is currently 0x0fff (4095), bump the clock sequence counter, since we're rolling over.
        if ($sequence === 0x0fff) {
            $this->clockSequenceCounter++;
        }

        if ($millisecondsShifted > $milliseconds) {
            /** @var int<0, max> $identifier */
            $identifier = $millisecondsShifted | $this->workerProcessIdShifted | $sequence;
        } else {
            /** @var numeric-string $identifier */
            $identifier = (string) BigInteger::of($milliseconds)
                ->shiftedLeft(self::TIMESTAMP_BIT_SHIFTS)
                ->or($this->workerProcessIdShifted)
                ->or($sequence);
        }

        return new DiscordSnowflake($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromHexadecimal(string $identifier): DiscordSnowflake
    {
        return new DiscordSnowflake($this->convertFromHexadecimal($identifier));
    }

    /**
     * @param int<0, max> | numeric-string $identifier
     *
     * @throws InvalidArgument
     */
    public function createFromInteger(int | string $identifier): DiscordSnowflake
    {
        return new DiscordSnowflake($identifier);
    }

    /**
     * @param numeric-string $identifier
     *
     * @throws InvalidArgument
     */
    public function createFromString(string $identifier): DiscordSnowflake
    {
        return new DiscordSnowflake($identifier);
    }
}
