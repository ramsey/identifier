<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is open source software: you can distribute it and/or modify it under the terms of the MIT License
 * (the "License"). You may not use this file except in compliance with the License.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license https://opensource.org/licenses/MIT MIT License
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
use Ramsey\Identifier\Snowflake\Internal\StandardFactory;
use Ramsey\Identifier\SnowflakeFactory;

use function sprintf;

/**
 * A factory that generates Snowflake identifiers for use with the Twitter social media platform.
 *
 * @link http://twitter.com Twitter.
 * @link https://github.com/twitter-archive/snowflake/tree/snowflake-2010 Snowflake.
 * @see TwitterSnowflake
 */
final class TwitterSnowflakeFactory implements SnowflakeFactory
{
    use StandardFactory;

    private const TIMESTAMP_BIT_SHIFTS = 22;

    /**
     * For performance, we'll prepare the machine ID bits and store them for repeated use.
     */
    private readonly int $machineIdShifted;

    /**
     * We increase this value each time our clock sequence rolls over and add the value to the milliseconds to ensure
     * the values are monotonically increasing.
     */
    private int $clockSequenceCounter = 0;

    /**
     * @param int $machineId A machine identifier to use when creating Snowflakes; we take the modulo of this integer
     *     divided by 1024, giving it an effective range of 0-1023 (i.e., 10 bits).
     * @param Clock $clock A clock used to provide a date-time instance; defaults to {@see SystemClock}.
     * @param ClockSequence $sequence A clock sequence value to prevent collisions; defaults to {@see MonotonicClockSequence}.
     */
    public function __construct(
        private readonly int $machineId,
        private readonly Clock $clock = new SystemClock(),
        private readonly ClockSequence $sequence = new MonotonicClockSequence(),
    ) {
        // Use modular arithmetic to roll over the machine ID value at mod 0x0400 (1024).
        $this->machineIdShifted = $this->machineId % 0x0400 << 12;
    }

    /**
     * @throws InvalidArgument
     */
    public function create(): TwitterSnowflake
    {
        return $this->createFromDateTime($this->clock->now());
    }

    /**
     * @param non-empty-string $identifier
     *
     * @throws InvalidArgument
     */
    public function createFromBytes(string $identifier): TwitterSnowflake
    {
        return new TwitterSnowflake($this->convertFromBytes($identifier));
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromDateTime(DateTimeInterface $dateTime): TwitterSnowflake
    {
        $milliseconds = (int) $dateTime->format('Uv') - Epoch::Twitter->value;

        if ($milliseconds < 0) {
            throw new InvalidArgument(sprintf(
                'Timestamp may not be earlier than the Twitter epoch, %s',
                Epoch::Twitter->toIso8601(),
            ));
        }

        // Use modular arithmetic to roll over the sequence value at mod 0x1000 (4096).
        $sequence = $this->sequence->next((string) $this->machineId, $dateTime) % 0x1000;

        // Increase the milliseconds by the current value of the clock sequence counter.
        $milliseconds += $this->clockSequenceCounter;
        $millisecondsShifted = $milliseconds << self::TIMESTAMP_BIT_SHIFTS;

        // If the sequence is currently 0x0fff (4095), bump the clock sequence counter, since we're rolling over.
        if ($sequence === 0x0fff) {
            $this->clockSequenceCounter++;
        }

        if ($millisecondsShifted > $milliseconds) {
            /** @var int<0, max> $identifier */
            $identifier = $millisecondsShifted | $this->machineIdShifted | $sequence;
        } else {
            /** @var numeric-string $identifier */
            $identifier = (string) BigInteger::of($milliseconds)
                ->shiftedLeft(self::TIMESTAMP_BIT_SHIFTS)
                ->or($this->machineIdShifted)
                ->or($sequence);
        }

        return new TwitterSnowflake($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromHexadecimal(string $identifier): TwitterSnowflake
    {
        return new TwitterSnowflake($this->convertFromHexadecimal($identifier));
    }

    /**
     * @param int<0, max> | numeric-string $identifier
     *
     * @throws InvalidArgument
     */
    public function createFromInteger(int | string $identifier): TwitterSnowflake
    {
        return new TwitterSnowflake($identifier);
    }

    /**
     * @param numeric-string $identifier
     *
     * @throws InvalidArgument
     */
    public function createFromString(string $identifier): TwitterSnowflake
    {
        return new TwitterSnowflake($identifier);
    }
}
