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

namespace Ramsey\Identifier\Ulid;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NegativeNumberException;
use DateTimeInterface;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Clock\SystemClock;
use Ramsey\Identifier\Service\RandomGenerator\PhpRandomGenerator;
use Ramsey\Identifier\Service\RandomGenerator\RandomGenerator;
use Ramsey\Identifier\Ulid\Utility\Validation;
use Ramsey\Identifier\UlidFactory;
use Ramsey\Identifier\UlidIdentifier;
use Ramsey\Identifier\Uuid\Utility\Format;
use Ramsey\Identifier\Uuid\Utility\Time;
use StellaMaris\Clock\ClockInterface as Clock;

use function is_int;
use function is_string;
use function pack;
use function sprintf;
use function str_pad;
use function strlen;
use function strspn;

use const PHP_INT_MAX;
use const PHP_INT_SIZE;
use const STR_PAD_LEFT;

/**
 * A factory that uses default services to generate ULIDs
 */
final class DefaultUlidFactory implements UlidFactory
{
    use Validation;

    private readonly Time $time;

    /**
     * Constructs a factory for creating ULIDs
     *
     * @param Clock $clock A clock used to provide a date-time instance;
     *     defaults to {@see SystemClock}
     * @param RandomGenerator $randomGenerator A random generator used to
     *     generate bytes; defaults to {@see PhpRandomGenerator}
     */
    public function __construct(
        private readonly Clock $clock = new SystemClock(),
        private readonly RandomGenerator $randomGenerator = new PhpRandomGenerator(),
    ) {
        $this->time = new Time();
    }

    /**
     * @throws InvalidArgument
     */
    public function create(): UlidIdentifier
    {
        $dateTime = $this->clock->now();
        $bytes = $this->time->getTimeBytesForUnixEpoch($dateTime)
            . $this->randomGenerator->bytes(10);

        return new Ulid($bytes);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromBytes(string $identifier): UlidIdentifier
    {
        if ($this->isMax($identifier, Format::FORMAT_BYTES)) {
            return new MaxUlid();
        }

        if ($this->isNil($identifier, Format::FORMAT_BYTES)) {
            return new NilUlid();
        }

        if (strlen($identifier) === Format::FORMAT_BYTES) {
            return new Ulid($identifier);
        }

        throw new InvalidArgument('Identifier must be a 16-byte string');
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromDateTime(DateTimeInterface $dateTime): UlidIdentifier
    {
        $bytes = $this->time->getTimeBytesForUnixEpoch($dateTime)
            . $this->randomGenerator->bytes(10);

        return new Ulid($bytes);
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromHexadecimal(string $identifier): UlidIdentifier
    {
        if ($this->isMax($identifier, Format::FORMAT_HEX)) {
            return new MaxUlid();
        }

        if ($this->isNil($identifier, Format::FORMAT_HEX)) {
            return new NilUlid();
        }

        if (strlen($identifier) === Format::FORMAT_HEX && $this->isValid($identifier, Format::FORMAT_HEX)) {
            return new Ulid($identifier);
        }

        throw new InvalidArgument('Identifier must be a 32-character hexadecimal string');
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromInteger(int | string $identifier): UlidIdentifier
    {
        if (
            is_string($identifier)
            && strspn($identifier, Format::MASK_INT) === strlen($identifier)
            && $identifier <= (string) PHP_INT_MAX
        ) {
            $identifier = (int) $identifier;
        }

        if (is_int($identifier)) {
            if ($identifier < 0) {
                throw new InvalidArgument('Unable to create a ULID from a negative integer');
            }

            $bytes = pack(PHP_INT_SIZE >= 8 ? 'J' : 'N', $identifier);
        } else {
            try {
                $bytes = BigInteger::of($identifier)->toBytes(false);
            } catch (NegativeNumberException $exception) {
                throw new InvalidArgument('Unable to create a ULID from a negative integer', 0, $exception);
            } catch (MathException $exception) {
                throw new InvalidArgument(sprintf('Invalid integer: "%s"', $identifier), 0, $exception);
            }
        }

        try {
            return $this->createFromBytes(str_pad($bytes, 16, "\x00", STR_PAD_LEFT));
        } catch (InvalidArgument) {
            throw new InvalidArgument(sprintf('Invalid ULID: %s', $identifier));
        }
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromString(string $identifier): UlidIdentifier
    {
        if ($this->isMax($identifier, Format::FORMAT_ULID)) {
            return new MaxUlid();
        }

        if ($this->isNil($identifier, Format::FORMAT_ULID)) {
            return new NilUlid();
        }

        if (strlen($identifier) === Format::FORMAT_ULID && $this->isValid($identifier, Format::FORMAT_ULID)) {
            return new Ulid($identifier);
        }

        throw new InvalidArgument('Identifier must be a valid ULID string representation');
    }

    public function max(): MaxUlid
    {
        return new MaxUlid();
    }

    public function nil(): NilUlid
    {
        return new NilUlid();
    }
}
