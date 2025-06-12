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

namespace Ramsey\Identifier\Ulid\Utility;

use function strlen;
use function strspn;
use function strtolower;
use function strtoupper;

/**
 * A utility providing common validation functionality for ULIDs.
 *
 * @internal Not intended for use outside ramsey/identifier; may change without notice.
 */
trait Validation
{
    /**
     * Used with {@see strtr()} to convert ULID decode symbols to the proper encode symbols.
     *
     * According to Crockford base-32, "When decoding, upper and lower case letters are accepted, and `i` and `l` will
     * be treated as `1` and `o` will be treated as `0`."
     */
    private const DECODE_SYMBOLS = ['from' => 'IiLlOo', 'to' => '111100'];

    /**
     * Returns true if the given Crockford base-32, hexadecimal, or bytes representation of a ULID is a Max ULID.
     */
    private function isMax(string $ulid, ?Format $format): bool
    {
        return match ($format) {
            Format::Ulid => strtoupper($ulid) === '7ZZZZZZZZZZZZZZZZZZZZZZZZZ',
            Format::Hex => strtolower($ulid) === 'ffffffffffffffffffffffffffffffff',
            Format::Bytes => $ulid === "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
            default => false,
        };
    }

    /**
     * Returns true if the given Crockford base-32, hexadecimal, or bytes representation of a ULID is a Nil ULID.
     */
    private function isNil(string $ulid, ?Format $format): bool
    {
        return match ($format) {
            Format::Ulid => $ulid === '00000000000000000000000000',
            Format::Hex => $ulid === '00000000000000000000000000000000',
            Format::Bytes => $ulid === "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
            default => false,
        };
    }

    /**
     * Returns true if the ULID is valid, according to the given format.
     *
     * @phpstan-assert-if-true non-empty-string $ulid
     */
    private function isValid(string $ulid, ?Format $format): bool
    {
        return match ($format) {
            Format::Ulid => strspn($ulid, Mask::CROCKFORD32) === Format::Ulid->value && $ulid[0] <= '7',
            Format::Hex => strspn($ulid, Mask::HEX) === Format::Hex->value,
            Format::Bytes => strlen($ulid) === Format::Bytes->value,
            default => false,
        };
    }
}
