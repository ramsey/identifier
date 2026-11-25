<?php

/**
 * This file is part of ramsey/identifier
 *
 * Copyright © Ben Ramsey <ben@ramsey.dev> and Contributors
 *
 * ramsey/identifier is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Lesser General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * ramsey/identifier is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with ramsey/identifier. If not, see <https://www.gnu.org/licenses/>.
 *
 * SPDX-License-Identifier: LGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Ramsey\Identifier;

use Identifier\BytesIdentifierFactory;
use Identifier\IntegerIdentifierFactory;
use Identifier\StringIdentifierFactory;
use Override;
use Ramsey\Identifier\Exception\InvalidArgument;

/**
 * A factory for creating UUIDs
 */
interface UuidFactory extends
    BytesIdentifierFactory,
    IntegerIdentifierFactory,
    StringIdentifierFactory
{
    #[Override]
    public function create(): Uuid;

    #[Override]
    public function createFromBytes(string $identifier): Uuid;

    /**
     * Creates a new instance of a UUID from the given hexadecimal
     * representation
     *
     * @throws InvalidArgument MUST throw if the identifier is not a legal
     *     value
     */
    public function createFromHexadecimal(string $identifier): Uuid;

    /**
     * @param int<0, max> | numeric-string $identifier
     */
    #[Override]
    public function createFromInteger(int | string $identifier): Uuid;

    #[Override]
    public function createFromString(string $identifier): Uuid;
}
