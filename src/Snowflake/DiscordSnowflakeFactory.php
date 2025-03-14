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

namespace Ramsey\Identifier\Snowflake;

use Brick\Math\BigInteger;
use DateTimeInterface;
use Psr\Clock\ClockInterface as Clock;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Clock\Precision;
use Ramsey\Identifier\Service\Clock\Sequence;
use Ramsey\Identifier\Service\Clock\StatefulSequence;
use Ramsey\Identifier\Service\Clock\SystemClock;
use Ramsey\Identifier\Snowflake\Utility\StandardFactory;
use Ramsey\Identifier\SnowflakeFactory;

/**
 * A factory that generates Snowflakes according to Discord's rules
 *
 * @link https://discord.com/developers/docs/reference#snowflakes Discord Snowflakes
 */
final class DiscordSnowflakeFactory implements SnowflakeFactory
{
    use StandardFactory;

    /**
     * For performance, we'll prepare the worker and process ID bits and store
     * them for repeated use.
     */
    private readonly int $workerProcessIdShifted;

    /**
     * Constructs a factory for creating Discord Snowflakes
     *
     * @param int<0, 31> $workerId A 5-bit worker identifier to use when
     *     creating Snowflakes
     * @param int<0, 31> $processId A 5-bit process identifier to use when
     *     creating Snowflakes
     * @param Clock $clock A clock used to provide a date-time instance;
     *     defaults to {@see SystemClock}
     * @param Sequence $sequence A sequence that provides a clock sequence value
     *     to prevent collisions; defaults to {@see StatefulSequence} with
     *     millisecond precision
     */
    public function __construct(
        private readonly int $workerId,
        private readonly int $processId,
        private readonly Clock $clock = new SystemClock(),
        private readonly Sequence $sequence = new StatefulSequence(precision: Precision::Millisecond),
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
        $milliseconds = (int) $dateTime->format('Uv') - Epoch::Discord->value;

        if ($milliseconds < 0) {
            throw new InvalidArgument(
                'Timestamp may not be earlier than the Discord epoch, 2015-01-01 00:00:00.000 +00:00',
            );
        }

        $sequence = $this->sequence->value($this->workerId + $this->processId, $dateTime) & 0x0fff;

        $millisecondsShifted = $milliseconds << 22;

        if ($millisecondsShifted > $milliseconds) {
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
     * @throws InvalidArgument
     */
    public function createFromInteger(int | string $identifier): DiscordSnowflake
    {
        return new DiscordSnowflake($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromString(string $identifier): DiscordSnowflake
    {
        /** @var numeric-string $value */
        $value = $identifier;

        return new DiscordSnowflake($value);
    }
}
