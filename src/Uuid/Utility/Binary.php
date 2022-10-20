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

namespace Ramsey\Identifier\Uuid\Utility;

use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid\Variant;
use Ramsey\Identifier\Uuid\Version;

use function pack;
use function strlen;
use function unpack;

/**
 * @internal
 */
final class Binary
{
    /**
     * Applies the RFC 4122 version number and variant field to the 128-bit
     * integer (as a 16-byte string) provided
     *
     * @param non-empty-string $bytes A 128-bit integer (16-byte string) to
     *     which the RFC 4122 version number and variant field will be applied,
     *     making the number a valid UUID
     * @param Version | null $version The RFC 4122 version to apply
     * @param Variant $variant The variant to apply
     *
     * @return non-empty-string A 16-byte string with the UUID version and variant applied
     *
     * @throws InvalidArgument
     *
     * @psalm-pure
     */
    public function applyVersionAndVariant(
        string $bytes,
        ?Version $version,
        Variant $variant = Variant::Rfc4122,
    ): string {
        if (strlen($bytes) !== 16) {
            throw new InvalidArgument('$bytes must be a a 16-byte string');
        }

        /** @var int[] $parts */
        $parts = unpack('n*', $bytes);

        if ($version !== null) {
            $parts[4] = $parts[4] & 0x0fff;
            $parts[4] |= $version->value << 12;
        }

        $parts[5] = match ($variant) {
            Variant::ReservedNcs => $parts[5] & 0x7fff,
            Variant::Rfc4122 => $parts[5] & 0x3fff | 0x8000,
            Variant::ReservedMicrosoft => $parts[5] & 0x1fff | 0xc000,
            Variant::ReservedFuture => $parts[5] & 0x1fff | 0xe000,
        };

        /** @var non-empty-string */
        return pack('n*', ...$parts);
    }
}
