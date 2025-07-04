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

use Brick\Math\BigInteger;

use function is_int;
use function is_string;
use function pack;
use function sprintf;
use function str_pad;
use function strlen;
use function strspn;

use const STR_PAD_LEFT;

/**
 * An enum representing the format of a Snowflake identifier.
 *
 * This enum class includes static utilities for formatting Snowflakes from integer or numeric string forms into
 * hexadecimal, byte, or int forms.
 *
 * @internal Not intended for use outside ramsey/identifier; may change without notice.
 */
enum Format: int
{
    /**
     * Bytes representation.
     */
    case Bytes = 8;

    /**
     * Hexadecimal representation.
     */
    case Hex = 16;

    /**
     * Formats a Snowflake identifier from its integer or numeric string form into {@see self::Hex}, {@see self::Bytes},
     * or int forms.
     */
    public static function format(int | string $value, ?self $to): int | string
    {
        if (is_string($value) && self::isStringInt($value) && is_int($value + 0)) {
            $value = (int) $value;
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

    /**
     * @phpstan-assert-if-true numeric-string $value
     */
    private static function isStringInt(string $value): bool
    {
        return strspn($value, Mask::INT) === strlen($value);
    }
}
