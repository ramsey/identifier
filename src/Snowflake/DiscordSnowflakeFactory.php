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
use Ramsey\Identifier\Service\Clock\Precision;
use Ramsey\Identifier\Service\Clock\SystemClock;
use Ramsey\Identifier\Snowflake\Utility\StandardFactory;
use Ramsey\Identifier\SnowflakeFactory;

use function sprintf;

/**
 * A factory that generates Snowflake identifiers for use with the Discord voice, text, and streaming video platform.
 *
 * @link https://discord.com Discord.
 * @link https://discord.com/developers/docs/reference#snowflakes Discord Snowflakes.
 * @see DiscordSnowflake
 */
final class DiscordSnowflakeFactory implements SnowflakeFactory
{
    use StandardFactory;

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
     * Constructs a factory for creating Discord Snowflakes.
     *
     * @param int<0, 31> $workerId A 5-bit worker identifier to use when creating Snowflakes.
     * @param int<0, 31> $processId A 5-bit process identifier to use when creating Snowflakes.
     * @param Clock $clock A clock used to provide a date-time instance; defaults to {@see SystemClock}.
     * @param ClockSequence $sequence A clock sequence value to prevent collisions; defaults to {@see MonotonicClockSequence}.
     */
    public function __construct(
        private readonly int $workerId,
        private readonly int $processId,
        private readonly Clock $clock = new SystemClock(),
        private readonly ClockSequence $sequence = new MonotonicClockSequence(),
    ) {
        $this->workerProcessIdShifted = ($this->workerId & 0x1f) << 17 | ($this->processId & 0x1f) << 12;
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

        $sequence = $this->sequence->next((string) ($this->workerId + $this->processId), $dateTime) & 0x0fff;

        // Increase the milliseconds by the current value of the clock sequence counter.
        $milliseconds += $this->clockSequenceCounter;
        $millisecondsShifted = $milliseconds << 22;

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
                ->shiftedLeft(22)
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
