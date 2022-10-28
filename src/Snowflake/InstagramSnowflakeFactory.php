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
use Identifier\BinaryIdentifierFactory;
use Identifier\DateTimeIdentifierFactory;
use Identifier\IntegerIdentifierFactory;
use Identifier\StringIdentifierFactory;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Clock\Sequence;
use Ramsey\Identifier\Service\Clock\StatefulSequence;
use Ramsey\Identifier\Service\Clock\SystemClock;
use Ramsey\Identifier\Service\Os\Os;
use Ramsey\Identifier\Service\Os\PhpOs;
use Ramsey\Identifier\Snowflake\Utility\StandardFactory;
use StellaMaris\Clock\ClockInterface as Clock;

/**
 * A factory that generates Snowflakes according to Instagram's rules
 *
 * @link https://instagram-engineering.com/sharding-ids-at-instagram-1cf5a71e5a5c Instagram Snowflakes
 */
final class InstagramSnowflakeFactory implements
    BinaryIdentifierFactory,
    DateTimeIdentifierFactory,
    IntegerIdentifierFactory,
    StringIdentifierFactory
{
    use StandardFactory;

    private readonly bool $is64Bit;

    /**
     * For performance, we'll prepare the shared ID bits and store them for repeated use.
     */
    private readonly int $shardIdShifted;

    /**
     * Constructs a factory for creating Instagram Snowflakes
     *
     * @param int $shardId A 13-bit shard identifier to use when creating
     *     Snowflakes
     * @param Clock $clock A clock used to provide a date-time instance;
     *     defaults to {@see SystemClock}
     * @param Sequence $sequence A sequence that provides a clock sequence value
     *     to prevent collisions; defaults to {@see StatefulSequence}
     */
    public function __construct(
        private readonly int $shardId,
        private readonly Clock $clock = new SystemClock(),
        private readonly Sequence $sequence = new StatefulSequence(precision: StatefulSequence::PRECISION_MSEC),
        Os $os = new PhpOs(),
    ) {
        $this->is64Bit = $os->getIntSize() >= 8;
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
        $milliseconds = $dateTime->format('Uv');

        if ($this->is64Bit) {
            $milliseconds = (int) $milliseconds - (int) Epoch::Instagram->value;
        } else {
            $milliseconds = (string) BigInteger::of($milliseconds)->minus(Epoch::Instagram->value);
        }

        if ($milliseconds < 0) {
            throw new InvalidArgument(
                'Timestamp may not be earlier than the Instagram epoch, 2011-08-24 21:07:01.721 +00:00',
            );
        }

        $sequence = $this->sequence->value($this->shardId, $dateTime) & 0x03ff;

        if ($this->is64Bit) {
            /** @var int<0, max> $identifier */
            $identifier = (int) $milliseconds << 23 | $this->shardIdShifted | $sequence;
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

    protected function is64Bit(): bool
    {
        return $this->is64Bit;
    }
}
