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
use Ramsey\Identifier\Service\Clock\Sequence;
use Ramsey\Identifier\Service\Clock\StatefulSequence;
use Ramsey\Identifier\Service\Clock\SystemClock;
use Ramsey\Identifier\Snowflake\Utility\StandardFactory;
use Ramsey\Identifier\SnowflakeFactory;

/**
 * A factory that generates Snowflakes according to Twitter's rules
 *
 * @link https://github.com/twitter-archive/snowflake/tree/snowflake-2010 Twitter Snowflakes
 */
final class TwitterSnowflakeFactory implements SnowflakeFactory
{
    use StandardFactory;

    /**
     * For performance, we'll prepare the machine ID bits and store them
     * for repeated use.
     */
    private readonly int $machineIdShifted;

    /**
     * Constructs a factory for creating Twitter Snowflakes
     *
     * @param int<0, 1023> $machineId A 10-bit machine identifier to use when
     *     creating Snowflakes
     * @param Clock $clock A clock used to provide a date-time instance;
     *     defaults to {@see SystemClock}
     * @param Sequence $sequence A sequence that provides a clock sequence value
     *     to prevent collisions; defaults to {@see StatefulSequence}
     */
    public function __construct(
        private readonly int $machineId,
        private readonly Clock $clock = new SystemClock(),
        private readonly Sequence $sequence = new StatefulSequence(precision: StatefulSequence::PRECISION_MSEC),
    ) {
        $this->machineIdShifted = ($this->machineId & 0x03ff) << 12;
    }

    /**
     * @throws InvalidArgument
     */
    public function create(): TwitterSnowflake
    {
        return $this->createFromDateTime($this->clock->now());
    }

    /**
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
            throw new InvalidArgument(
                'Timestamp may not be earlier than the Twitter epoch, 2010-11-04 01:42:54.657 +00:00',
            );
        }

        $sequence = $this->sequence->value($this->machineId, $dateTime) & 0x0fff;

        $millisecondsShifted = $milliseconds << 22;

        if ($millisecondsShifted > $milliseconds) {
            $identifier = $millisecondsShifted | $this->machineIdShifted | $sequence;
        } else {
            /** @var numeric-string $identifier */
            $identifier = (string) BigInteger::of($milliseconds)
                ->shiftedLeft(22)
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
     * @throws InvalidArgument
     */
    public function createFromInteger(int | string $identifier): TwitterSnowflake
    {
        return new TwitterSnowflake($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromString(string $identifier): TwitterSnowflake
    {
        /** @var numeric-string $value */
        $value = $identifier;

        return new TwitterSnowflake($value);
    }
}
