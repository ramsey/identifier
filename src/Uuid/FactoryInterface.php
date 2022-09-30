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

namespace Ramsey\Identifier\Uuid;

use DateTimeInterface;
use Identifier\Uuid\UuidFactoryInterface;
use Identifier\Uuid\UuidInterface;
use Ramsey\Identifier\Exception\InvalidArgumentException;

/**
 * Describes a common interface for UUID factories used with ramsey/identifier
 */
interface FactoryInterface extends UuidFactoryInterface
{
    /**
     * Creates a Max UUID with all bits set to one (1)
     */
    public function max(): MaxUuid;

    /**
     * Creates a Nil UUID with all bits set to zero (0)
     */
    public function nil(): NilUuid;

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
    public function uuid1(
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV1;

    /**
     * Creates a version 2, DCE Security UUID
     *
     * @param Dce\Domain $localDomain The local domain to which the local
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
    public function uuid2(
        Dce\Domain $localDomain = Dce\Domain::Person,
        ?int $localIdentifier = null,
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV2;

    /**
     * Creates a version 3, name-based (MD5) UUID
     *
     * @param non-empty-string | UuidInterface $namespace
     *
     * @throws InvalidArgumentException
     */
    public function uuid3(string | UuidInterface $namespace, string $name): UuidV3;

    /**
     * Creates a version 4, random UUID
     */
    public function uuid4(): UuidV4;

    /**
     * Creates a version 5, name-based (SHA-1) UUID
     *
     * @param non-empty-string | UuidInterface $namespace
     *
     * @throws InvalidArgumentException
     */
    public function uuid5(string | UuidInterface $namespace, string $name): UuidV5;

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
    public function uuid6(
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV6;

    /**
     * Creates a version 7, Unix Epoch time UUID
     *
     * @param DateTimeInterface | null $dateTime A date-time to use when
     *     creating the identifier
     *
     * @throws InvalidArgumentException
     */
    public function uuid7(?DateTimeInterface $dateTime = null): UuidV7;

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
    public function uuid8(
        string $customFieldA,
        string $customFieldB,
        string $customFieldC,
    ): UuidV8;
}
