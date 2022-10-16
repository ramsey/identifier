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

use Identifier\BinaryIdentifier;
use Identifier\DateTimeIdentifier;
use Identifier\IntegerIdentifier;

/**
 * Describes the interface of a universally unique lexicographically sortable
 * identifier (ULID)
 *
 * @link https://github.com/ulid/spec ULID Specification
 */
interface UlidIdentifier extends BinaryIdentifier, DateTimeIdentifier, IntegerIdentifier
{
    /**
     * Returns a string representation of the ULID encoded as hexadecimal digits
     */
    public function toHexadecimal(): string;
}
