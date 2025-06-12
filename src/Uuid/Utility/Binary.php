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

namespace Ramsey\Identifier\Uuid\Utility;

use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid\Variant;
use Ramsey\Identifier\Uuid\Version;

use function pack;
use function strlen;
use function unpack;

/**
 * Binary utilities for UUID generation.
 *
 * @internal Not intended for use outside ramsey/identifier; may change without notice.
 */
final class Binary
{
    /**
     * Applies the version number and variant field to the 128-bit integer (as a 16-byte string) provided.
     *
     * @param non-empty-string $bytes A 128-bit integer (16-byte string) to which the version number and variant field
     *     will be applied, making the number a valid UUID.
     * @param Version | null $version The version to apply.
     * @param Variant $variant The variant to apply.
     *
     * @return non-empty-string A 16-byte string with the UUID version and variant applied.
     *
     * @throws InvalidArgument
     */
    public function applyVersionAndVariant(
        string $bytes,
        ?Version $version,
        Variant $variant = Variant::Rfc,
    ): string {
        if (strlen($bytes) !== 16) {
            throw new InvalidArgument(
                'When applying the version and variant bits, the bytes must be a 16-byte octet string',
            );
        }

        /** @var int[] $parts */
        $parts = unpack('n8', $bytes);

        if ($version !== null) {
            $parts[4] = $parts[4] & 0x0fff;
            $parts[4] |= $version->value << 12;
        }

        $parts[5] = match ($variant) {
            Variant::Ncs => $parts[5] & 0x7fff,
            Variant::Rfc => $parts[5] & 0x3fff | 0x8000,
            Variant::Microsoft => $parts[5] & 0x1fff | 0xc000,
            Variant::Future => $parts[5] & 0x1fff | 0xe000,
        };

        /** @var non-empty-string */
        return pack('n8', ...$parts);
    }
}
