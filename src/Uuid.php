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
use Identifier\Uuid\NodeBasedUuidInterface;
use Identifier\Uuid\TimeBasedUuidInterface;
use Identifier\Uuid\UuidInterface;
use Ramsey\Identifier\Uuid\DceDomain;
use Ramsey\Identifier\Uuid\Factory;
use Ramsey\Identifier\Uuid\FactoryInterface;
use Ramsey\Identifier\Uuid\MaxUuid;
use Ramsey\Identifier\Uuid\NilUuid;
use Ramsey\Identifier\Uuid\UuidV1;
use Ramsey\Identifier\Uuid\UuidV2;
use Ramsey\Identifier\Uuid\UuidV3;
use Ramsey\Identifier\Uuid\UuidV4;
use Ramsey\Identifier\Uuid\UuidV5;
use Ramsey\Identifier\Uuid\UuidV6;
use Ramsey\Identifier\Uuid\UuidV7;
use Ramsey\Identifier\Uuid\UuidV8;

/**
 * Uuid provides constants and static methods for working with and generating UUIDs
 */
final class Uuid
{
    private static ?FactoryInterface $factory = null;

    /**
     * Creates a UUID from a byte string
     *
     * @param string $bytes A binary string
     *
     * @throws InvalidArgumentException
     */
    public static function fromBytes(string $bytes): UuidInterface
    {
        return self::getFactory()->createFromBytes($bytes);
    }

    /**
     * Creates a UUID from a string standard representation
     *
     * @param string $uuid A string standard representation of a UUID
     *
     * @throws InvalidArgumentException
     */
    public static function fromString(string $uuid): UuidInterface
    {
        return self::getFactory()->createFromString($uuid);
    }

    /**
     * Creates a UUID from a date-time instance
     *
     * If `$node` or `$clockSequence` are provided, this creates a version 6,
     * reordered time UUID (i.e., a {@see NodeBasedUuidInterface} UUID).
     * However, if only a date-time is provided, this creates a version 7, Unix
     * Epoch time UUID (i.e., a {@see TimeBasedUuidInterface} UUID).
     *
     * @param DateTimeInterface $dateTime A date-time to use when creating the
     *     identifier
     * @param int<0, max> | non-empty-string | null $node A 48-bit integer or
     *     hexadecimal string representing the hardware address of the machine
     *     where this identifier was generated
     * @param int<0, 16383> | null $clockSequence A 14-bit number used to help
     *     avoid duplicates that could arise when the clock is set backwards in
     *     time or if the node ID changes
     *
     * @throws InvalidArgumentException
     */
    public static function fromDateTime(
        DateTimeInterface $dateTime,
        int | string | null $node = null,
        ?int $clockSequence = null,
    ): NodeBasedUuidInterface | TimeBasedUuidInterface {
        if ($node !== null || $clockSequence !== null) {
            return self::getFactory()->uuid6($node, $clockSequence, $dateTime);
        }

        return self::getFactory()->uuid7($dateTime);
    }

    /**
     * Creates a UUID from a 32-character hexadecimal string
     *
     * @throws InvalidArgumentException
     */
    public static function fromHexadecimal(string $hexadecimal): UuidInterface
    {
        return self::getFactory()->createFromHexadecimal($hexadecimal);
    }

    /**
     * Creates a UUID from a 128-bit integer string
     *
     * @param int | numeric-string $integer This value may be an `int` if it
     *     falls within the range of `PHP_INT_MIN` - `PHP_INT_MAX`; however, if
     *     it is outside this range, it must be a string representation of the
     *     integer
     *
     * @throws InvalidArgumentException
     */
    public static function fromInteger(int | string $integer): UuidInterface
    {
        return self::getFactory()->createFromInteger($integer);
    }

    /**
     * Creates a Max UUID with all bits set to one (1)
     */
    public static function max(): MaxUuid
    {
        return self::getFactory()->max();
    }

    /**
     * Creates a Nil UUID with all bits set to zero (0)
     */
    public static function nil(): NilUuid
    {
        return self::getFactory()->nil();
    }

    /**
     * Creates a version 1, Gregorian time UUID
     *
     * @param int<0, max> | string | null $node A 48-bit integer or hexadecimal
     *     string representing the hardware address of the machine where this
     *     identifier was generated
     * @param int<0, 16383> | null $clockSequence A 14-bit number used to help
     *     avoid duplicates that could arise when the clock is set backwards in
     *     time or if the node ID changes
     * @param DateTimeInterface | null $dateTime A date-time to use when
     *     creating the identifier
     *
     * @throws InvalidArgumentException
     *
     * @psalm-param int<0, max> | non-empty-string | null $node
     */
    public static function v1(
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV1 {
        return self::getFactory()->uuid1($node, $clockSequence, $dateTime);
    }

    /**
     * Creates a version 2, DCE Security UUID
     *
     * @param DceDomain $localDomain The local domain to which the local
     *     identifier belongs; this defaults to "Person," and if $localIdentifier
     *     is not provided, the factory will attempt to obtain a suitable local
     *     ID for the domain (e.g., the UID or GID of the user running the script)
     * @param int<0, max> | null $localIdentifier A local identifier belonging
     *     to the local domain specified in $localDomain; if no identifier is
     *     provided, the factory will attempt to obtain a suitable local ID for
     *     the domain (e.g., the UID or GID of the user running the script)
     * @param int<0, max> | string | null $node A 48-bit integer or hexadecimal
     *     string representing the hardware address of the machine where this
     *     identifier was generated
     * @param int<0, 63> | null $clockSequence A 6-bit number used to help
     *     avoid duplicates that could arise when the clock is set backwards in
     *     time or if the node ID changes
     * @param DateTimeInterface | null $dateTime A date-time to use when
     *     creating the identifier
     *
     * @throws InvalidArgumentException
     *
     * @psalm-param int<0, max> | non-empty-string | null $node
     */
    public static function v2(
        DceDomain $localDomain = DceDomain::Person,
        ?int $localIdentifier = null,
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV2 {
        return self::getFactory()->uuid2($localDomain, $localIdentifier, $node, $clockSequence, $dateTime);
    }

    /**
     * Creates a version 3, name-based (MD5) UUID
     *
     * @param non-empty-string | UuidInterface $namespace
     *
     * @throws InvalidArgumentException
     */
    public static function v3(string | UuidInterface $namespace, string $name): UuidV3
    {
        return self::getFactory()->uuid3($namespace, $name);
    }

    /**
     * Creates a version 4, random UUID
     */
    public static function v4(): UuidV4
    {
        return self::getFactory()->uuid4();
    }

    /**
     * Creates a version 5, name-based (SHA-1) UUID
     *
     * @param non-empty-string | UuidInterface $namespace
     *
     * @throws InvalidArgumentException
     */
    public static function v5(string | UuidInterface $namespace, string $name): UuidV5
    {
        return self::getFactory()->uuid5($namespace, $name);
    }

    /**
     * Creates a version 6, reordered time UUID
     *
     * @param int<0, max> | string | null $node A 48-bit integer or hexadecimal
     *     string representing the hardware address of the machine where this
     *     identifier was generated
     * @param int<0, 16383> | null $clockSequence A 14-bit number used to help
     *     avoid duplicates that could arise when the clock is set backwards in
     *     time or if the node ID changes
     * @param DateTimeInterface | null $dateTime A date-time to use when
     *     creating the identifier
     *
     * @throws InvalidArgumentException
     *
     * @psalm-param int<0, max> | non-empty-string | null $node
     */
    public static function v6(
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV6 {
        return self::getFactory()->uuid6($node, $clockSequence, $dateTime);
    }

    /**
     * Creates a version 7, Unix Epoch time UUID
     *
     * @param DateTimeInterface | null $dateTime A date-time to use when
     *     creating the identifier
     *
     * @throws InvalidArgumentException
     */
    public static function v7(?DateTimeInterface $dateTime = null): UuidV7
    {
        return self::getFactory()->uuid7($dateTime);
    }

    /**
     * Creates a version 8, custom UUID
     *
     * The three custom fields, A, B, and C, may contain any values according to
     * your application's needs. Be aware, however, that other implementations
     * may not understand the semantics of the values.
     *
     * @param string $customFieldA An arbitrary 48-bit (12-character)
     *     hexadecimal string
     * @param string $customFieldB An arbitrary 12-bit (3-character)
     *     hexadecimal string
     * @param string $customFieldC An arbitrary 64-bit (16-character)
     *     hexadecimal string (if set, the 2 most significant bits will be lost,
     *     since they are replaced with the variant bits, so don't rely on these
     *     bits to hold any important data; in other words, treat this as a
     *     62-bit value)
     *
     * @throws InvalidArgumentException
     */
    public static function v8(string $customFieldA, string $customFieldB, string $customFieldC): UuidV8
    {
        return self::getFactory()->uuid8($customFieldA, $customFieldB, $customFieldC);
    }

    private static function getFactory(): FactoryInterface
    {
        if (self::$factory === null) {
            self::$factory = new Factory();
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
