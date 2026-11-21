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

namespace Ramsey\Identifier\Snowflake\Internal;

use Ramsey\Identifier\Internal\IntegerString;

use function is_int;
use function strcmp;
use function strlen;

/**
 * A utility providing common validation functionality for Snowflake
 * identifiers
 *
 * @internal Not intended for use outside ramsey/identifier; may change without
 *     notice.
 */
trait Validation
{
    /**
     * The maximum possible value of a Snowflake (i.e., 0xffffffffffffffff)
     */
    private const UPPER_BOUNDS = '18446744073709551615';

    /**
     * Returns true if the Snowflake identifier is valid
     */
    private function isValid(int | string $snowflake): bool
    {
        if (is_int($snowflake)) {
            return $snowflake >= 0;
        }

        if (!IntegerString::isValid($snowflake)) {
            return false;
        }

        $length = strlen(self::UPPER_BOUNDS);

        if (strlen($snowflake) !== $length) {
            return strlen($snowflake) < $length;
        }

        return strcmp($snowflake, self::UPPER_BOUNDS) <= 0;
    }
}
