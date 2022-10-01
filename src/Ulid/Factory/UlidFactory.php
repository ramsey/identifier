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

namespace Ramsey\Identifier\Ulid\Factory;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NegativeNumberException;
use DateTimeInterface;
use Identifier\Ulid\UlidFactoryInterface;
use Identifier\Ulid\UlidInterface;
use Ramsey\Identifier\Exception\InvalidArgumentException;
use Ramsey\Identifier\Exception\RandomSourceException;
use Ramsey\Identifier\Service\Random\RandomBytesService;
use Ramsey\Identifier\Service\Random\RandomServiceInterface;
use Ramsey\Identifier\Service\Time\CurrentDateTimeService;
use Ramsey\Identifier\Service\Time\TimeServiceInterface;
use Ramsey\Identifier\Ulid\MaxUlid;
use Ramsey\Identifier\Ulid\NilUlid;
use Ramsey\Identifier\Ulid\Ulid;
use Ramsey\Identifier\Ulid\Utility\Validation;
use Ramsey\Identifier\Uuid\Utility\Format;
use Ramsey\Identifier\Uuid\Utility\Time;

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

final class UlidFactory implements UlidFactoryInterface
{
    use Validation;

    /**
     * Constructs a factory for creating ULIDs
     *
     * @param RandomServiceInterface $randomService A service used to generate
     *     random bytes; defaults to {@see RandomBytesService}
     * @param TimeServiceInterface $timeService A service used to provide a
     *     date-time instance; defaults to {@see CurrentDateTimeService}
     */
    public function __construct(
        private readonly RandomServiceInterface $randomService = new RandomBytesService(),
        private readonly TimeServiceInterface $timeService = new CurrentDateTimeService(),
    ) {
    }

    /**
     * @throws InvalidArgumentException
     * @throws RandomSourceException
     */
    public function create(): Ulid
    {
        $dateTime = $this->timeService->getDateTime();
        $bytes = Time::getTimeBytesForUnixEpoch($dateTime)
            . $this->randomService->getRandomBytes(10);

        return new Ulid($bytes);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createFromBytes(string $identifier): UlidInterface
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

        throw new InvalidArgumentException('Identifier must be a 16-byte string');
    }

    /**
     * @throws InvalidArgumentException
     * @throws RandomSourceException
     */
    public function createFromDateTime(DateTimeInterface $dateTime): UlidInterface
    {
        $bytes = Time::getTimeBytesForUnixEpoch($dateTime)
            . $this->randomService->getRandomBytes(10);

        return new Ulid($bytes);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createFromHexadecimal(string $identifier): UlidInterface
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

        throw new InvalidArgumentException('Identifier must be a 32-character hexadecimal string');
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createFromInteger(int | string $identifier): UlidInterface
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
                throw new InvalidArgumentException('Unable to create a ULID from a negative integer');
            }

            $bytes = pack(PHP_INT_SIZE >= 8 ? 'J*' : 'N*', $identifier);
        } else {
            try {
                $bytes = BigInteger::of($identifier)->toBytes(false);
            } catch (NegativeNumberException $exception) {
                throw new InvalidArgumentException('Unable to create a ULID from a negative integer', 0, $exception);
            } catch (MathException $exception) {
                throw new InvalidArgumentException(sprintf('Invalid integer: "%s"', $identifier), 0, $exception);
            }
        }

        try {
            return $this->createFromBytes(str_pad($bytes, 16, "\x00", STR_PAD_LEFT));
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException(sprintf('Invalid ULID: %s', $identifier));
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createFromString(string $identifier): UlidInterface
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

        throw new InvalidArgumentException('Identifier must be a valid ULID string representation');
    }
}
