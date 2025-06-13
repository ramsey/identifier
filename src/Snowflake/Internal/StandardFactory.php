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
use Ramsey\Identifier\Exception\InvalidArgument;

use function hexdec;
use function strlen;
use function strspn;
use function unpack;

/**
 * Provides common methods for factories of Snowflake identifiers.
 *
 * @internal Not intended for use outside ramsey/identifier; may change without notice.
 */
trait StandardFactory
{
    /**
     * @param non-empty-string $identifier
     *
     * @return int<0, max> | numeric-string
     *
     * @throws InvalidArgument
     */
    private function convertFromBytes(string $identifier): int | string
    {
        if (strlen($identifier) !== Format::Bytes->value) {
            throw new InvalidArgument('The identifier must be an 8-byte octet string');
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
     * @return int<0, max> | numeric-string
     *
     * @throws InvalidArgument
     */
    private function convertFromHexadecimal(string $identifier): int | string
    {
        if (strlen($identifier) !== Format::Hex->value || strspn($identifier, Mask::HEX) !== strlen($identifier)) {
            throw new InvalidArgument('The identifier must be a 16-character hexadecimal string');
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
