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
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\MicrosoftGuid;
use Ramsey\Identifier\Uuid\Variant;
use Ramsey\Identifier\Uuid\Version;

use function chr;
use function hex2bin;
use function ord;
use function strlen;

/**
 * Binary utilities for UUID generation
 *
 * @internal Not intended for use outside ramsey/identifier; may change without
 *     notice.
 */
final class Binary
{
    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Applies the version number and variant field to the 128-bit integer (as a
     * 16-byte string) provided
     *
     * @param non-empty-string $bytes A 128-bit integer (16-byte string) to
     *     which the version number and variant field will be applied, making
     *     the number a valid UUID
     * @param Version | null $version The version to apply
     * @param Variant $variant The variant to apply
     *
     * @return non-empty-string A 16-byte string with the UUID version and
     *     variant applied.
     *
     * @throws InvalidArgument
     */
    public static function applyVersionAndVariant(
        string $bytes,
        ?Version $version,
        Variant $variant = Variant::Rfc,
    ): string {
        if (strlen($bytes) !== 16) {
            throw new InvalidArgument(
                'When applying the version and variant bits, the bytes must be a 16-byte octet string',
            );
        }

        if ($version !== null) {
            $bytes[6] = chr(ord($bytes[6]) & 0x0f | $version->value << 4);
        }

        $bytes[8] = chr(match ($variant) {
            Variant::Ncs => ord($bytes[8]) & 0x7f,
            Variant::Rfc => ord($bytes[8]) & 0x3f | 0x80,
            Variant::Microsoft => ord($bytes[8]) & 0x1f | 0xc0,
            Variant::Future => ord($bytes[8]) & 0x1f | 0xe0,
        });

        return $bytes;
    }

    /**
     * Returns the 16 bytes of the UUID in network byte order, which is the
     * layout RFC 9562 defines
     *
     * A Microsoft GUID returns its bytes in GUID byte order, but its
     * hexadecimal form is its value, so the bytes come from that.
     *
     * @return non-empty-string
     */
    public static function getNetworkBytes(Uuid $uuid): string
    {
        if ($uuid instanceof MicrosoftGuid) {
            /** @var non-empty-string */
            return (string) hex2bin($uuid->toHexadecimal());
        }

        return $uuid->toBytes();
    }
}
