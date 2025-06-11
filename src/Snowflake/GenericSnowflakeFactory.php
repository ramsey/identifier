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
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Clock\ClockInterface as Clock;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Clock\ClockSequence;
use Ramsey\Identifier\Service\Clock\MonotonicClockSequence;
use Ramsey\Identifier\Service\Clock\Precision;
use Ramsey\Identifier\Service\Clock\SystemClock;
use Ramsey\Identifier\Snowflake;
use Ramsey\Identifier\Snowflake\Utility\StandardFactory;
use Ramsey\Identifier\SnowflakeFactory;

use function sprintf;
use function substr;

/**
 * A factory that generates generic Snowflakes identifiers that may use any epoch offset.
 *
 * This uses the commonly adopted Twitter Snowflake specification, allowing for an arbitrary epoch offset.
 *
 * @link https://en.wikipedia.org/wiki/Snowflake_ID Snowflake ID.
 * @link https://github.com/twitter-archive/snowflake/tree/snowflake-2010 Twitter Snowflake identifiers.
 * @see GenericSnowflake
 */
final class GenericSnowflakeFactory implements SnowflakeFactory
{
    use StandardFactory;

    /**
     * We increase this value each time our clock sequence rolls over and add the value to the milliseconds to ensure
     * the values are monotonically increasing.
     */
    private int $clockSequenceCounter = 0;

    /**
     * For performance, we'll prepare the node ID bits and store for later use.
     */
    private readonly int $nodeIdShifted;

    private readonly int $epochOffset;

    /**
     * Constructs a factory for creating Snowflakes
     *
     * @param int<0, 1023> $nodeId A 10-bit machine identifier to use when creating Snowflakes
     * @param Epoch | int $epochOffset The offset from the Unix Epoch in milliseconds to use when creating Snowflakes
     * @param Clock $clock A clock used to provide a date-time instance; defaults to {@see SystemClock}
     * @param ClockSequence $sequence A clock sequence value to prevent collisions; defaults to {@see MonotonicClockSequence}
     */
    public function __construct(
        private readonly int $nodeId,
        Epoch | int $epochOffset,
        private readonly Clock $clock = new SystemClock(),
        private readonly ClockSequence $sequence = new MonotonicClockSequence(),
    ) {
        $this->nodeIdShifted = ($this->nodeId & 0x03ff) << 12;
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
        $milliseconds = (int) $dateTime->format(Precision::Millisecond->value) - $this->epochOffset;

        if ($milliseconds < 0) {
            throw new InvalidArgument(sprintf(
                'Timestamp may not be earlier than the epoch, %s',
                $this->getEpochDate()->format(Epoch::ISO_EXTENDED_FORMAT),
            ));
        }

        $sequence = $this->sequence->next((string) $this->nodeId, $dateTime) & 0x0fff;

        // Increase the milliseconds by the current value of the clock sequence counter.
        $milliseconds += $this->clockSequenceCounter;
        $millisecondsShifted = $milliseconds << 22;

        // If the sequence is currently 0x0fff (4095), bump the clock sequence counter, since we're rolling over.
        if ($sequence === 0x0fff) {
            $this->clockSequenceCounter++;
        }

        if ($millisecondsShifted > $milliseconds) {
            $identifier = $millisecondsShifted | $this->nodeIdShifted | $sequence;
        } else {
            /** @var numeric-string $identifier */
            $identifier = (string) BigInteger::of($milliseconds)
                ->shiftedLeft(22)
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
     * @throws InvalidArgument
     */
    public function createFromInteger(int | string $identifier): Snowflake
    {
        return new GenericSnowflake($identifier, $this->epochOffset);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromString(string $identifier): Snowflake
    {
        /** @var numeric-string $value */
        $value = $identifier;

        return new GenericSnowflake($value, $this->epochOffset);
    }

    private function getEpochDate(): DateTimeImmutable
    {
        $timestamp = sprintf('%04s', $this->epochOffset);
        $timestamp = substr($timestamp, 0, -3) . '.' . substr($timestamp, -3);

        return new DateTimeImmutable('@' . $timestamp);
    }
}
