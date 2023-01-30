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
 * A factory that generates Snowflakes according to Instagram's rules
 *
 * @link https://instagram-engineering.com/sharding-ids-at-instagram-1cf5a71e5a5c Instagram Snowflakes
 */
final class InstagramSnowflakeFactory implements SnowflakeFactory
{
    use StandardFactory;

    /**
     * For performance, we'll prepare the shared ID bits and store them for repeated use.
     */
    private readonly int $shardIdShifted;

    /**
     * Constructs a factory for creating Instagram Snowflakes
     *
     * @param int<0, 8191> $shardId A 13-bit shard identifier to use when
     *     creating Snowflakes
     * @param Clock $clock A clock used to provide a date-time instance;
     *     defaults to {@see SystemClock}
     * @param Sequence $sequence A sequence that provides a clock sequence value
     *     to prevent collisions; defaults to {@see StatefulSequence}
     */
    public function __construct(
        private readonly int $shardId,
        private readonly Clock $clock = new SystemClock(),
        private readonly Sequence $sequence = new StatefulSequence(precision: StatefulSequence::PRECISION_MSEC),
    ) {
        $this->shardIdShifted = ($this->shardId & 0x1fff) << 10;
    }

    /**
     * @throws InvalidArgument
     */
    public function create(): InstagramSnowflake
    {
        return $this->createFromDateTime($this->clock->now());
    }

    /**
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
        $milliseconds = (int) $dateTime->format('Uv') - Epoch::Instagram->value;

        if ($milliseconds < 0) {
            throw new InvalidArgument(
                'Timestamp may not be earlier than the Instagram epoch, 2011-08-24 21:07:01.721 +00:00',
            );
        }

        $sequence = $this->sequence->value($this->shardId, $dateTime) & 0x03ff;

        $millisecondsShifted = $milliseconds << 23;

        if ($millisecondsShifted > $milliseconds) {
            $identifier = $millisecondsShifted | $this->shardIdShifted | $sequence;
        } else {
            /** @var numeric-string $identifier */
            $identifier = (string) BigInteger::of($milliseconds)
                ->shiftedLeft(23)
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
     * @throws InvalidArgument
     */
    public function createFromInteger(int | string $identifier): InstagramSnowflake
    {
        return new InstagramSnowflake($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromString(string $identifier): InstagramSnowflake
    {
        /** @var numeric-string $value */
        $value = $identifier;

        return new InstagramSnowflake($value);
    }
}
