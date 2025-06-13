<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser
 * General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * ramsey/identifier is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with ramsey/identifier. If not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@ramsey.dev> and Contributors
 * @license https://opensource.org/license/lgpl-3-0/ GNU Lesser General Public License version 3 or later
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
 * A factory for creating Snowflake IDs.
 */
interface SnowflakeFactory extends
    BytesIdentifierFactory,
    DateTimeIdentifierFactory,
    IntegerIdentifierFactory,
    StringIdentifierFactory
{
    public function create(): Snowflake;

    public function createFromBytes(string $identifier): Snowflake;

    public function createFromDateTime(DateTimeInterface $dateTime): Snowflake;

    /**
     * Creates a new instance of a Snowflake ID from the given hexadecimal representation.
     *
     * @throws InvalidArgument MUST throw if the identifier is not a legal value.
     */
    public function createFromHexadecimal(string $identifier): Snowflake;

    public function createFromInteger(int | string $identifier): Snowflake;

    public function createFromString(string $identifier): Snowflake;
}
