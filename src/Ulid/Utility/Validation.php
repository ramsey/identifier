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

namespace Ramsey\Identifier\Ulid\Utility;

use Ramsey\Identifier\Uuid\Utility\Format;

use function strspn;
use function strtolower;
use function strtoupper;
use function strtr;

/**
 * This internal trait provides common validation functionality for ULIDs
 *
 * @internal
 */
trait Validation
{
    /**
     * Returns true if the given Crockford base 32, hexadecimal, or bytes
     * representation of a ULID is a Max ULID
     */
    private function isMax(string $ulid, int $format): bool
    {
        return match ($format) {
            Format::FORMAT_ULID => strtoupper($ulid) === '7ZZZZZZZZZZZZZZZZZZZZZZZZZ',
            Format::FORMAT_HEX => strtolower($ulid) === 'ffffffffffffffffffffffffffffffff',
            Format::FORMAT_BYTES => $ulid === "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
            default => false,
        };
    }

    /**
     * Returns true if the given Crockford base 32, hexadecimal, or bytes
     * representation of a ULID is a Nil ULID
     */
    private function isNil(string $ulid, int $format): bool
    {
        return match ($format) {
            Format::FORMAT_ULID => $ulid === '00000000000000000000000000',
            Format::FORMAT_HEX => $ulid === '00000000000000000000000000000000',
            Format::FORMAT_BYTES => $ulid === "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
            default => false,
        };
    }

    /**
     * Returns true if the ULID is valid according to the given format
     */
    private function isValid(string $ulid, int $format): bool
    {
        return match ($format) {
            Format::FORMAT_ULID => (static function (string $ulid): bool {
                $ulid = strtr($ulid, 'IiLlOo', '111100');

                return strspn($ulid, Format::MASK_CROCKFORD32) === Format::FORMAT_ULID && $ulid[0] <= '7';
            })($ulid),
            Format::FORMAT_HEX => strspn($ulid, Format::MASK_HEX) === Format::FORMAT_HEX,
            Format::FORMAT_BYTES => true,
            default => false,
        };
    }
}
