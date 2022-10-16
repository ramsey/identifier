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
use Identifier\DateTimeIdentifierFactory;
use Identifier\Exception\InvalidArgument;
use Identifier\IntegerIdentifierFactory;
use Identifier\StringIdentifierFactory;

/**
 * Describes the interface of a factory for creating universally unique
 * lexicographically sortable identifiers (ULIDs)
 */
interface UlidFactory extends
    BinaryIdentifierFactory,
    DateTimeIdentifierFactory,
    IntegerIdentifierFactory,
    StringIdentifierFactory
{
    public function create(): UlidIdentifier;

    /**
     * @throws InvalidArgument MUST throw if $identifier is not a legal value
     */
    public function createFromBytes(string $identifier): UlidIdentifier;

    /**
     * @throws InvalidArgument MUST throw if $dateTime is not a legal value
     */
    public function createFromDateTime(DateTimeInterface $dateTime): UlidIdentifier;

    /**
     * Creates a new ULID from the given hexadecimal string representation
     *
     * @param string $identifier A hexadecimal-encoded representation of the ULID
     *
     * @throws InvalidArgument MUST throw if $identifier is not a legal value
     */
    public function createFromHexadecimal(string $identifier): UlidIdentifier;

    /**
     * @throws InvalidArgument MUST throw if $identifier is not a legal value
     */
    public function createFromInteger(int | string $identifier): UlidIdentifier;

    /**
     * @throws InvalidArgument MUST throw if $identifier is not a legal value
     */
    public function createFromString(string $identifier): UlidIdentifier;

    /**
     * Creates a Max ULID with all bits set to one (1)
     */
    public function max(): UlidIdentifier;

    /**
     * Creates a Nil ULID with all bits set to zero (0)
     */
    public function nil(): UlidIdentifier;
}
