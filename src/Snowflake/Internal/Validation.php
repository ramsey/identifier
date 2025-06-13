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

namespace Ramsey\Identifier\Snowflake\Internal;

use function is_int;
use function strlen;
use function strspn;

/**
 * A utility providing common validation functionality for Snowflake identifiers.
 *
 * @internal Not intended for use outside ramsey/identifier; may change without notice.
 */
trait Validation
{
    /**
     * The maximum possible value of a Snowflake (i.e., 0xffffffffffffffff).
     */
    private const UPPER_BOUNDS = '18446744073709551615';

    /**
     * Returns true if the Snowflake identifier is valid.
     */
    private function isValid(int | string $snowflake): bool
    {
        if (is_int($snowflake)) {
            return $snowflake >= 0;
        }

        if ($snowflake === '') {
            return false;
        }

        if (strspn($snowflake, Mask::INT) !== strlen($snowflake)) {
            return false;
        }

        return $snowflake <= self::UPPER_BOUNDS;
    }
}
