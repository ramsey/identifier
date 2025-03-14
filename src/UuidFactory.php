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

use Identifier\BytesIdentifierFactory;
use Identifier\IntegerIdentifierFactory;
use Identifier\StringIdentifierFactory;
use Ramsey\Identifier\Exception\InvalidArgument;

/**
 * A factory for creating UUIDs
 */
interface UuidFactory extends
    BytesIdentifierFactory,
    IntegerIdentifierFactory,
    StringIdentifierFactory
{
    public function create(): Uuid;

    public function createFromBytes(string $identifier): Uuid;

    /**
     * Creates a new instance of a UUID from the given hexadecimal representation
     *
     * @throws InvalidArgument MUST throw if the identifier is not a legal value
     */
    public function createFromHexadecimal(string $identifier): Uuid;

    public function createFromInteger(int | string $identifier): Uuid;

    public function createFromString(string $identifier): Uuid;
}
