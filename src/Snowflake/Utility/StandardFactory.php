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
use Ramsey\Identifier\Exception\InvalidArgument;

use function hexdec;
use function strlen;
use function strspn;
use function unpack;

/**
 * This internal trait provides common factory functionality for Snowflakes
 *
 * @internal
 */
trait StandardFactory
{
    /**
     * @return int | numeric-string
     *
     * @throws InvalidArgument
     */
    private function convertFromBytes(string $identifier): int | string
    {
        if (strlen($identifier) !== Format::FORMAT_BYTES) {
            throw new InvalidArgument('Identifier must be an 8-byte string');
        }

        /** @var int[] $parts */
        $parts = unpack('J', $identifier);

        // Support unsigned 64-bit identifiers.
        if ($parts[1] < 0) {
            /** @var numeric-string */
            return (string) BigInteger::fromBytes($identifier, false);
        }

        /** @var int<0, max> */
        return $parts[1];
    }

    /**
     * @return int | numeric-string
     *
     * @throws InvalidArgument
     */
    private function convertFromHexadecimal(string $identifier): int | string
    {
        if (
            strlen($identifier) !== Format::FORMAT_HEX
            || strspn($identifier, Format::MASK_HEX) !== strlen($identifier)
        ) {
            throw new InvalidArgument('Identifier must be a 16-character hexadecimal string');
        }

        // Support unsigned 64-bit identifiers.
        if ($identifier > '7fffffffffffffff') {
            /** @var numeric-string */
            return (string) BigInteger::fromBase($identifier, 16);
        }

        /** @var int<0, max> */
        return hexdec($identifier);
    }
}
