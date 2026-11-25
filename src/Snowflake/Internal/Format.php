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

use Brick\Math\BigInteger;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Internal\IntegerString;

use function is_int;
use function is_string;
use function pack;
use function sprintf;
use function str_pad;

use const STR_PAD_LEFT;

/**
 * An enum representing the format of a Snowflake identifier
 *
 * This enum class includes static utilities for formatting Snowflakes from
 * integer or numeric string forms into hexadecimal, byte, or int forms.
 *
 * @internal Not intended for use outside ramsey/identifier; may change without
 *     notice.
 */
enum Format: int
{
    /**
     * Bytes representation
     */
    case Bytes = 8;

    /**
     * Hexadecimal representation
     */
    case Hex = 16;

    /**
     * The largest unsigned 64-bit integer, which is the largest value a
     * Snowflake identifier has
     */
    private const string MAXIMUM = '18446744073709551615';

    /**
     * Formats a Snowflake identifier from its integer or numeric string form
     * into {@see self::Hex}, {@see self::Bytes}, or int forms
     *
     * @throws InvalidArgument if the value is negative, is a string that is not
     *     an integer string, or is greater than the largest unsigned 64-bit
     *     integer
     */
    public static function format(int | string $value, ?self $to): int | string
    {
        if (is_string($value)) {
            if (!IntegerString::isValid($value) || IntegerString::compare($value, self::MAXIMUM) > 0) {
                throw new InvalidArgument(sprintf('Invalid integer: "%s"', $value));
            }

            if (IntegerString::fitsNativeInteger($value)) {
                $value = (int) $value;
            }
        } elseif ($value < 0) {
            throw new InvalidArgument(sprintf('Invalid integer: "%d"', $value));
        }

        return match ($to) {
            self::Hex => is_int($value)
                ? sprintf('%016x', $value)
                : sprintf('%016s', BigInteger::of($value)->toBase(16)),
            self::Bytes => is_int($value)
                ? pack('J', $value)
                : str_pad(BigInteger::of($value)->toBytes(false), 8, "\x00", STR_PAD_LEFT),
            default => $value,
        };
    }

    /**
     * @return non-empty-string
     */
    public static function formatBytes(int | string $value): string
    {
        /** @var non-empty-string */
        return self::format($value, self::Bytes);
    }

    /**
     * @return non-empty-string
     */
    public static function formatHex(int | string $value): string
    {
        /** @var non-empty-string */
        return self::format($value, self::Hex);
    }

    /**
     * @return int<0, max> | numeric-string
     */
    public static function formatInt(int | string $value): int | string
    {
        /** @var int<0, max> | numeric-string */
        return self::format($value, null);
    }
}
