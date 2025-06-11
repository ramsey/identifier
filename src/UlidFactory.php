<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is open source software: you can distribute it and/or modify it under the terms of the MIT License
 * (the "License"). You may not use this file except in compliance with the License.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Ramsey\Identifier;

use DateTimeInterface;
use Identifier\BytesIdentifierFactory;
use Identifier\DateTimeIdentifierFactory;
use Identifier\IntegerIdentifierFactory;
use Identifier\StringIdentifierFactory;
use Ramsey\Identifier\Exception\InvalidArgument;

/**
 * A factory for creating ULIDs.
 */
interface UlidFactory extends
    BytesIdentifierFactory,
    DateTimeIdentifierFactory,
    IntegerIdentifierFactory,
    StringIdentifierFactory
{
    public function create(): Ulid;

    public function createFromBytes(string $identifier): Ulid;

    public function createFromDateTime(DateTimeInterface $dateTime): Ulid;

    /**
     * Creates a new instance of a ULID from the given hexadecimal representation.
     *
     * @throws InvalidArgument MUST throw if the identifier is not a legal value.
     */
    public function createFromHexadecimal(string $identifier): Ulid;

    public function createFromInteger(int | string $identifier): Ulid;

    public function createFromString(string $identifier): Ulid;
}
