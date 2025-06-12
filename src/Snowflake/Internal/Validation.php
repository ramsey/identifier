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
