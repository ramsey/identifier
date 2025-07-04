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
 * A factory that generates Snowflake identifiers for use with the Instagram photo and video sharing social media platform.
 *
 * @link https://www.instagram.com Instagram.
 * @see InstagramSnowflake
 */
final class InstagramSnowflakeFactory implements SnowflakeFactory
{
    use StandardFactory;

    private const TIMESTAMP_BIT_SHIFTS = 23;

    /**
     * We increase this value each time our clock sequence rolls over and add the value to the milliseconds to ensure
     * the values are monotonically increasing.
     */
    private int $clockSequenceCounter = 0;

    /**
     * For performance, we'll prepare the shared ID bits and store them for repeated use.
     */
    private readonly int $shardIdShifted;

    /**
     * @param int $shardId A shard identifier to use when creating Snowflakes; we take the modulo of this integer
     *     divided by 8192, giving it an effective range of 0-8191 (i.e., 13 bits).
     * @param Clock $clock A clock used to provide a date-time instance; defaults to {@see SystemClock}.
     * @param ClockSequence $sequence A clock sequence value to prevent collisions; defaults to {@see MonotonicClockSequence}.
     */
    public function __construct(
        private readonly int $shardId,
        private readonly Clock $clock = new SystemClock(),
        private readonly ClockSequence $sequence = new MonotonicClockSequence(),
    ) {
        // Use modular arithmetic to roll over the shard value at mod 0x2000 (8192).
        $this->shardIdShifted = $this->shardId % 0x2000 << 10;
    }

    /**
     * @throws InvalidArgument
     */
    public function create(): InstagramSnowflake
    {
        return $this->createFromDateTime($this->clock->now());
    }

    /**
     * @param non-empty-string $identifier
     *
     * @throws InvalidArgument
     */
    public function createFromBytes(string $identifier): InstagramSnowflake
    {
        return new InstagramSnowflake($this->convertFromBytes($identifier));
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromDateTime(DateTimeInterface $dateTime): InstagramSnowflake
    {
        $milliseconds = (int) $dateTime->format(Precision::Millisecond->value) - Epoch::Instagram->value;

        if ($milliseconds < 0) {
            throw new InvalidArgument(sprintf(
                'Timestamp may not be earlier than the Instagram epoch, %s',
                Epoch::Instagram->toIso8601(),
            ));
        }

        if ($milliseconds > 0x1ffffffffff) {
            throw new InvalidArgument(
                'Instagram Snowflakes cannot have a date-time greater than 2081-04-30T12:54:37.272Z',
            );
        }

        // Use modular arithmetic to roll over the sequence value at mod 0x0400 (1024).
        $sequence = $this->sequence->next((string) $this->shardId, $dateTime) % 0x0400;

        // Increase the milliseconds by the current value of the clock sequence counter.
        $milliseconds += $this->clockSequenceCounter;
        $millisecondsShifted = $milliseconds << self::TIMESTAMP_BIT_SHIFTS;

        // If the sequence is currently 0x03ff (1023), bump the clock sequence counter, since we're rolling over.
        if ($sequence === 0x03ff) {
            $this->clockSequenceCounter++;
        }

        if ($millisecondsShifted > $milliseconds) {
            /** @var int<0, max> $identifier */
            $identifier = $millisecondsShifted | $this->shardIdShifted | $sequence;
        } else {
            /** @var numeric-string $identifier */
            $identifier = (string) BigInteger::of($milliseconds)
                ->shiftedLeft(self::TIMESTAMP_BIT_SHIFTS)
                ->or($this->shardIdShifted)
                ->or($sequence);
        }

        return new InstagramSnowflake($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromHexadecimal(string $identifier): InstagramSnowflake
    {
        return new InstagramSnowflake($this->convertFromHexadecimal($identifier));
    }

    /**
     * @param int<0, max> | numeric-string $identifier
     *
     * @throws InvalidArgument
     */
    public function createFromInteger(int | string $identifier): InstagramSnowflake
    {
        return new InstagramSnowflake($identifier);
    }

    /**
     * @param numeric-string $identifier
     *
     * @throws InvalidArgument
     */
    public function createFromString(string $identifier): InstagramSnowflake
    {
        return new InstagramSnowflake($identifier);
    }
}
