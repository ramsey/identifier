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

namespace Ramsey\Identifier\Uuid\Internal;

use Ramsey\Identifier\Exception\InvalidArgument;
use ReflectionClass;

use function chr;
use function ord;
use function strlen;

/**
 * Creates a UUID of the class's version from any 16 bytes
 *
 * @internal Not intended for use outside ramsey/identifier; may change without
 *     notice.
 */
trait FromRawBytes
{
    /**
     * Creates the UUID from any 16 bytes, setting the version and variant
     *
     * @internal Not intended for use outside ramsey/identifier; may change
     *     without notice.
     *
     * @throws InvalidArgument if the string is not 16 bytes long
     */
    public static function fromRawBytes(string $bytes): static
    {
        if (strlen($bytes) !== 16) {
            throw new InvalidArgument(
                'When applying the version and variant bits, the bytes must be a 16-byte octet string',
            );
        }

        /** @var ReflectionClass<static> | null $reflection */
        static $reflection = null;
        $reflection ??= new ReflectionClass(static::class);

        $uuid = $reflection->newInstanceWithoutConstructor();

        $bytes[6] = chr(ord($bytes[6]) & 0x0f | $uuid->getVersion()->value << 4);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);

        $uuid->uuid = $bytes; // @phpstan-ignore property.readOnlyAssignNotInConstructor
        $uuid->format = Format::Bytes; // @phpstan-ignore property.readOnlyAssignNotInConstructor

        return $uuid;
    }
}
