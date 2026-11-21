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

namespace Ramsey\Identifier\Internal;

use function strcmp;
use function strlen;
use function strspn;
use function substr;

use const PHP_INT_MAX;

/**
 * Checks whether a string is an integer string
 *
 * An integer string is one or more of the digits 0 to 9, with no sign, no
 * decimal point, no exponent, and no whitespace. Zero is "0," and no other
 * integer string begins with a zero. Every integer therefore has exactly one
 * integer string.
 *
 * @internal Not intended for use outside ramsey/identifier; may change without
 *     notice.
 */
final class IntegerString
{
    private const DIGITS = '0123456789';

    private function __construct()
    {
    }

    /**
     * Returns true if the value is an integer string
     *
     * @phpstan-assert-if-true non-empty-string & numeric-string $value
     */
    public static function isValid(string $value): bool
    {
        $length = strlen($value);

        if ($length === 0 || strspn($value, self::DIGITS) !== $length) {
            return false;
        }

        return $length === 1 || $value[0] !== '0';
    }

    /**
     * Returns true if the value is a minus sign followed by an integer string
     * other than "0"
     */
    public static function isNegative(string $value): bool
    {
        if ($value === '' || $value[0] !== '-' || $value === '-0') {
            return false;
        }

        return self::isValid(substr($value, 1));
    }

    /**
     * Returns a negative integer, zero, or a positive integer when the first
     * integer string is less than, equal to, or greater than the second
     *
     * Both arguments must be integer strings; the result for any other string
     * has no meaning.
     */
    public static function compare(string $first, string $second): int
    {
        $lengthComparison = strlen($first) <=> strlen($second);

        if ($lengthComparison !== 0) {
            return $lengthComparison;
        }

        return strcmp($first, $second);
    }

    /**
     * Returns true if the integer string is no greater than the largest
     * integer PHP stores as an int
     *
     * The argument must be an integer string; the result for any other string
     * has no meaning.
     */
    public static function fitsNativeInteger(string $value): bool
    {
        return self::compare($value, (string) PHP_INT_MAX) <= 0;
    }
}
