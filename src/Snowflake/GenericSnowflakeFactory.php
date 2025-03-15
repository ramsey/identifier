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
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Clock\ClockInterface as Clock;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Clock\Precision;
use Ramsey\Identifier\Service\Clock\Sequence;
use Ramsey\Identifier\Service\Clock\StatefulSequence;
use Ramsey\Identifier\Service\Clock\SystemClock;
use Ramsey\Identifier\Snowflake;
use Ramsey\Identifier\Snowflake\Utility\StandardFactory;
use Ramsey\Identifier\SnowflakeFactory;

use function sprintf;
use function substr;

/**
 * A factory that generates Snowflakes
 *
 * @link https://en.wikipedia.org/wiki/Snowflake_ID Snowflakes
 */
final class GenericSnowflakeFactory implements SnowflakeFactory
{
    use StandardFactory;

    /**
     * For performance, we'll prepare the node ID bits and store for later use.
     */
    private readonly int $nodeIdShifted;

    /**
     * Constructs a factory for creating Snowflakes
     *
     * @param int<0, 1023> $nodeId A 10-bit machine identifier to use when
     *     creating Snowflakes
     * @param int $epochOffset The offset from the Unix Epoch in milliseconds to
     *     use when creating Snowflakes
     * @param Clock $clock A clock used to provide a date-time instance;
     *     defaults to {@see SystemClock}
     * @param Sequence $sequence A sequence that provides a clock sequence value
     *     to prevent collisions; defaults to {@see StatefulSequence} with
     *     millisecond precision
     */
    public function __construct(
        private readonly int $nodeId,
        private readonly int $epochOffset,
        private readonly Clock $clock = new SystemClock(),
        private readonly Sequence $sequence = new StatefulSequence(precision: Precision::Millisecond),
    ) {
        $this->nodeIdShifted = ($this->nodeId & 0x03ff) << 12;
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
        $milliseconds = (int) $dateTime->format('Uv') - $this->epochOffset;

        if ($milliseconds < 0) {
            throw new InvalidArgument(sprintf(
                'Timestamp may not be earlier than the epoch, %s',
                $this->getEpochDate()->format(Epoch::ISO_EXTENDED_FORMAT),
            ));
        }

        $sequence = $this->sequence->value($this->nodeId, $dateTime) & 0x0fff;

        $millisecondsShifted = $milliseconds << 22;

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
