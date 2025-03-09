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

use Brick\Math\BigInteger;

use function is_int;
use function is_string;
use function pack;
use function sprintf;
use function str_pad;

use const STR_PAD_LEFT;

/**
 * @internal
 */
final class Format
{
    /**
     * Bytes representation
     */
    public const FORMAT_BYTES = 8;

    /**
     * Hexadecimal representation
     */
    public const FORMAT_HEX = 16;

    /**
     * String integer representation
     *
     * This has a value of `-1`, since the string integers that make up
     * Snowflakes will have varying string lengths, unlike bytes or hex
     * representations which have fixed lengths.
     */
    public const FORMAT_INT = -1;

    /**
     * A mask used with functions like {@see strspn()} to validate hexadecimal strings
     */
    public const MASK_HEX = '0123456789abcdefABCDEF';

    /**
     * A mask used with functions like {@see strspn()} to validate string integers
     */
    public const MASK_INT = '0123456789';

    public function __construct()
    {
    }

    /**
     * Formats a Snowflake identifier from its integer or numeric string form
     * into {@see self::FORMAT_HEX}, {@see self::FORMAT_BYTES}, or
     * {@see self::FORMAT_INT} forms.
     *
     * @param self::FORMAT_* $to
     */
    public function format(int | string $value, int $to): int | string
    {
        /**
         * @phpstan-ignore-next-line
         */
        if (is_string($value) && is_int($value + 0)) {
            $value = (int) $value;
        }

        return match ($to) {
            self::FORMAT_HEX => is_int($value)
                ? sprintf('%016x', $value)
                : sprintf('%016s', BigInteger::of($value)->toBase(16)),
            self::FORMAT_BYTES => is_int($value)
                ? pack('J', $value)
                : str_pad(BigInteger::of($value)->toBytes(false), 8, "\x00", STR_PAD_LEFT),
            default => $value,
        };
    }
}
