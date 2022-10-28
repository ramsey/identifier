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
     * Returns true if the system is 64-bit
     */
    abstract protected function is64Bit(): bool;

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

        if ($this->is64Bit()) {
            /** @var int[] $parts */
            $parts = unpack('J', $identifier);

            /** @var int<0, max> */
            return $parts[1];
        }

        /** @var numeric-string */
        return (string) BigInteger::fromBytes($identifier);
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

        if ($this->is64Bit()) {
            /** @var int<0, max> */
            return hexdec($identifier);
        }

        /** @var numeric-string */
        return (string) BigInteger::fromBase($identifier, 16);
    }
}
