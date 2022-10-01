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

namespace Ramsey\Identifier;

use DateTimeInterface;
use Identifier\Exception\InvalidArgumentException;
use Identifier\Ulid\UlidFactoryInterface;
use Identifier\Ulid\UlidInterface;
use Ramsey\Identifier\Ulid\Factory\UlidFactory;
use Ramsey\Identifier\Ulid\MaxUlid;
use Ramsey\Identifier\Ulid\NilUlid;

/**
 * Ulid provides constants and static methods for working with and generating ULIDs
 */
final class Ulid
{
    private static ?UlidFactoryInterface $factory = null;

    /**
     * Creates a ULID
     */
    public static function create(): UlidInterface
    {
        return self::getFactory()->create();
    }

    /**
     * Creates a ULID from a byte string
     *
     * @param string $bytes A binary string
     *
     * @throws InvalidArgumentException
     */
    public static function fromBytes(string $bytes): UlidInterface
    {
        return self::getFactory()->createFromBytes($bytes);
    }

    /**
     * Creates a ULID from a string representation
     *
     * @param string $uuid A string representation of a ULID
     *
     * @throws InvalidArgumentException
     */
    public static function fromString(string $uuid): UlidInterface
    {
        return self::getFactory()->createFromString($uuid);
    }

    /**
     * Creates a ULID from a date-time instance
     *
     * @param DateTimeInterface $dateTime A date-time to use when creating the
     *     identifier
     *
     * @throws InvalidArgumentException
     */
    public static function fromDateTime(DateTimeInterface $dateTime): UlidInterface
    {
        return self::getFactory()->createFromDateTime($dateTime);
    }

    /**
     * Creates a ULID from a 32-character hexadecimal string
     *
     * @throws InvalidArgumentException
     */
    public static function fromHexadecimal(string $hexadecimal): UlidInterface
    {
        return self::getFactory()->createFromHexadecimal($hexadecimal);
    }

    /**
     * Creates a ULID from a 128-bit integer string
     *
     * @param int | numeric-string $integer This value may be an `int` if it
     *     falls within the range of `PHP_INT_MIN` - `PHP_INT_MAX`; however, if
     *     it is outside this range, it must be a string representation of the
     *     integer
     *
     * @throws InvalidArgumentException
     */
    public static function fromInteger(int | string $integer): UlidInterface
    {
        return self::getFactory()->createFromInteger($integer);
    }

    /**
     * Creates a Max ULID with all bits set to one (1)
     */
    public static function max(): MaxUlid
    {
        return new MaxUlid();
    }

    /**
     * Creates a Nil ULID with all bits set to zero (0)
     */
    public static function nil(): NilUlid
    {
        return new NilUlid();
    }

    private static function getFactory(): UlidFactoryInterface
    {
        if (self::$factory === null) {
            self::$factory = new UlidFactory();
        }

        return self::$factory;
    }

    /**
     * Disallow public instantiation
     */
    private function __construct()
    {
    }
}
