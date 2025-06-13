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

use Identifier\BytesIdentifier;
use Identifier\DateTimeIdentifier;
use Identifier\IntegerIdentifier;

/**
 * A universally unique lexicographically sortable identifier (ULID).
 *
 * A ULID consists of a 48-bit integer representing the milliseconds since the Unix Epoch and 80 bits of randomness from
 * a cryptographically secure source. Together, they form a 128-bit unsigned integer that is binary-compatible with
 * UUIDs. When encoded in string form, ULIDs use Crockford base-32 encoding, allowing 5 bits per character. As a result,
 * string ULIDs are 26 characters instead of the 36 characters that make up a UUID.
 *
 * @link https://github.com/ulid/spec ULID specification.
 * @link https://www.crockford.com/base32.html Crockford base-32 specification.
 */
interface Ulid extends BytesIdentifier, DateTimeIdentifier, IntegerIdentifier
{
    /**
     * Returns a string representation of the ULID encoded as hexadecimal digits.
     */
    public function toHexadecimal(): string;
}
