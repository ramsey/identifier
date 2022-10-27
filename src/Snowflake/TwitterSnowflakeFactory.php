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
use Ramsey\Identifier\Snowflake\Utility\Format;
use Ramsey\Identifier\Snowflake\Utility\Validation;
use StellaMaris\Clock\ClockInterface as Clock;

use function hexdec;
use function strlen;
use function strspn;
use function unpack;

/**
 * A factory that generates Snowflakes according to Twitter's rules
 *
 * @link https://github.com/twitter-archive/snowflake/tree/snowflake-2010 Twitter Snowflakes
 */
final class TwitterSnowflakeFactory implements
    BinaryIdentifierFactory,
    DateTimeIdentifierFactory,
    IntegerIdentifierFactory,
    StringIdentifierFactory
{
    use Validation;

    private readonly bool $is64Bit;

    /**
     * We retain the original machine ID to pass when obtaining the next
     * sequence value.
     */
    private readonly int $machineId;

    /**
     * For performance, we'll prepare the machine ID bits and store them
     * for repeated use.
     */
    private readonly int $machineIdShifted;

    /**
     * Constructs a factory for creating ULIDs
     */
    public function __construct(
        int $machineId,
        private readonly Clock $clock = new SystemClock(),
        private readonly Sequence $sequence = new StatefulSequence(precision: StatefulSequence::PRECISION_MSEC),
        Os $os = new PhpOs(),
    ) {
        $this->is64Bit = $os->getIntSize() >= 8;
        $this->machineId = $machineId;
        $this->machineIdShifted = ($machineId & 0x03ff) << 12;
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
        if (strlen($identifier) !== Format::FORMAT_BYTES) {
            throw new InvalidArgument('Identifier must be an 8-byte string');
        }

        if ($this->is64Bit) {
            /** @var int[] $parts */
            $parts = unpack('J', $identifier);

            /** @var int<0, max> $identifier */
            $identifier = $parts[1];
        } else {
            /** @var numeric-string $identifier */
            $identifier = (string) BigInteger::fromBytes($identifier);
        }

        return new TwitterSnowflake($identifier);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromDateTime(DateTimeInterface $dateTime): TwitterSnowflake
    {
        $milliseconds = $dateTime->format('Uv');

        if ($this->is64Bit) {
            $milliseconds = (int) $milliseconds - (int) TwitterSnowflake::TWITTER_EPOCH_OFFSET;
        } else {
            $milliseconds = (string) BigInteger::of($milliseconds)->minus(TwitterSnowflake::TWITTER_EPOCH_OFFSET);
        }

        if ($milliseconds < 0) {
            throw new InvalidArgument(
                'Timestamp may not be earlier than the Twitter epoch, 2010-11-04T01:42:54.657+00:00',
            );
        }

        $sequence = $this->sequence->value($this->machineId, $dateTime) & 0x0fff;

        if ($this->is64Bit) {
            /** @var int<0, max> $identifier */
            $identifier = (int) $milliseconds << 22 | $this->machineIdShifted | $sequence;
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
        if (
            strlen($identifier) !== Format::FORMAT_HEX
            || strspn($identifier, Format::MASK_HEX) !== strlen($identifier)
        ) {
            throw new InvalidArgument('Identifier must be a 16-character hexadecimal string');
        }

        if ($this->is64Bit) {
            /** @var int<0, max> $value */
            $value = hexdec($identifier);
        } else {
            /** @var numeric-string $value */
            $value = (string) BigInteger::fromBase($identifier, 16);
        }

        return new TwitterSnowflake($value);
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
