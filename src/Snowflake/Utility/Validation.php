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

namespace Ramsey\Identifier\Snowflake\Utility;

use function is_int;
use function strlen;
use function strspn;

/**
 * This internal trait provides common validation functionality for Snowflakes
 *
 * @internal
 *
 * @psalm-immutable
 */
trait Validation
{
    /**
     * The maximum possible value of a Snowflake (i.e., 0xffffffffffffffff)
     */
    private const UPPER_BOUNDS = '18446744073709551615';

    /**
     * Returns true if the Snowflake is valid according to the given format
     */
    private function isValid(int | string $snowflake): bool
    {
        if (is_int($snowflake)) {
            return $snowflake >= 0;
        }

        if ($snowflake === '') {
            return false;
        }

        if (strspn($snowflake, Format::MASK_INT) !== strlen($snowflake)) {
            return false;
        }

        return $snowflake <= self::UPPER_BOUNDS;
    }
}
