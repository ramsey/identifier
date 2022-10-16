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
use Identifier\BinaryIdentifierFactory;
use Identifier\Exception\InvalidArgument;
use Identifier\IntegerIdentifierFactory;
use Identifier\StringIdentifierFactory;
use Ramsey\Identifier\Uuid\DceDomain;

/**
 * Describes the interface of a factory for creating universally unique
 * identifiers (UUIDs)
 */
interface UuidFactory extends
    BinaryIdentifierFactory,
    IntegerIdentifierFactory,
    StringIdentifierFactory
{
    public function create(): UuidIdentifier;

    /**
     * @throws InvalidArgument MUST throw if $identifier is not a legal value
     */
    public function createFromBytes(string $identifier): UuidIdentifier;

    /**
     * Creates a new UUID from the given hexadecimal string representation
     *
     * @param string $identifier A hexadecimal-encoded representation of the UUID
     *
     * @throws InvalidArgument MUST throw if $identifier is not a legal value
     */
    public function createFromHexadecimal(string $identifier): UuidIdentifier;

    /**
     * @throws InvalidArgument MUST throw if $identifier is not a legal value
     */
    public function createFromInteger(int | string $identifier): UuidIdentifier;

    /**
     * @throws InvalidArgument MUST throw if $identifier is not a legal value
     */
    public function createFromString(string $identifier): UuidIdentifier;

    /**
     * Creates a Max UUID with all bits set to one (1)
     */
    public function max(): UuidIdentifier;

    /**
     * Creates a Nil UUID with all bits set to zero (0)
     */
    public function nil(): UuidIdentifier;

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
     * @throws InvalidArgument MUST throw if parameters are not legal values
     *
     * @psalm-param int<0, max> | non-empty-string | null $node
     */
    public function v1(
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): NodeBasedUuidIdentifier;

    /**
     * Creates a version 2, DCE Security UUID
     *
     * @param DceDomain $localDomain The local domain to which the local
     *     identifier belongs; this MUST default to a suitable domain for the
     *     implementation
     * @param int<0, max> | null $localIdentifier A local identifier belonging
     *     to the local domain specified in $localDomain; if no identifier is
     *     provided, the factory SHOULD attempt to obtain a suitable local ID
     *     for the domain (e.g., the UID or GID of the user running the script)
     * @param int<0, max> | string | null $node A 48-bit integer or hexadecimal
     *     string representing the hardware address of the machine where this
     *     identifier was generated
     * @param int<0, 63> | null $clockSequence A 6-bit number used to help
     *     avoid duplicates that could arise when the clock is set backwards in
     *     time or if the node ID changes
     * @param DateTimeInterface | null $dateTime A date-time to use when
     *     creating the identifier
     *
     * @throws InvalidArgument MUST throw if parameters are not legal values
     *
     * @psalm-param int<0, max> | non-empty-string | null $node
     */
    public function v2(
        DceDomain $localDomain = DceDomain::Person,
        ?int $localIdentifier = null,
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): NodeBasedUuidIdentifier;

    /**
     * Creates a version 3, name-based (MD5) UUID
     *
     * @param string | UuidIdentifier $namespace The UUID namespace to use when
     *     creating this version 3 identifier
     * @param string $name The name used to create the version 3 identifier in
     *     the given namespace
     *
     * @throws InvalidArgument MUST throw if parameters are not legal values
     */
    public function v3(string | UuidIdentifier $namespace, string $name): UuidIdentifier;

    /**
     * Creates a version 4, random UUID
     */
    public function v4(): UuidIdentifier;

    /**
     * Creates a version 5, name-based (SHA-1) UUID
     *
     * @param string | UuidIdentifier $namespace The UUID namespace to use when
     *     creating this version 5 identifier
     * @param string $name The name used to create the version 5 identifier in
     *     the given namespace
     *
     * @throws InvalidArgument MUST throw if parameters are not legal values
     */
    public function v5(string | UuidIdentifier $namespace, string $name): UuidIdentifier;

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
     * @throws InvalidArgument MUST throw if parameters are not legal values
     *
     * @psalm-param int<0, max> | non-empty-string | null $node
     */
    public function v6(
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): NodeBasedUuidIdentifier;

    /**
     * Creates a version 7, Unix Epoch time UUID
     *
     * @param DateTimeInterface | null $dateTime A date-time to use when
     *     creating the identifier
     *
     * @throws InvalidArgument MUST throw if $dateTime is not a legal value
     */
    public function v7(?DateTimeInterface $dateTime = null): TimeBasedUuidIdentifier;

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
     * @throws InvalidArgument MUST throw if parameters are not legal values
     */
    public function v8(string $customFieldA, string $customFieldB, string $customFieldC): UuidIdentifier;
}
