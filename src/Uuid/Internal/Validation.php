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

namespace Ramsey\Identifier\Uuid\Internal;

use Ramsey\Identifier\Uuid\DceDomain;
use Ramsey\Identifier\Uuid\Variant;
use Ramsey\Identifier\Uuid\Version;

use function count;
use function explode;
use function hexdec;
use function strlen;
use function strspn;
use function strtolower;
use function substr;
use function unpack;

use const PHP_INT_MIN;

/**
 * A utility providing common validation functionality for UUIDs.
 *
 * @internal Not intended for use outside ramsey/identifier; may change without notice.
 */
trait Validation
{
    /**
     * Returns the Version enum for the UUID type represented by the class using this trait.
     *
     * We use this in {@see self::isValid()} to determine whether the UUID is valid for the type the class represents.
     */
    abstract protected function getVersion(): Version;

    /**
     * Given an integer value of the variant bits, this returns the variant associated with those bits.
     *
     * @link https://www.rfc-editor.org/rfc/rfc9562#section-4.1 RFC 9562, section 4.1. Variant Field.
     */
    private function determineVariant(int $value): Variant
    {
        return match (true) {
            $value >> 1 === 0b111 => Variant::Future,
            $value >> 1 === 0b110 => Variant::Microsoft,
            $value >> 2 === 0b10 => Variant::Rfc,
            default => Variant::Ncs,
        };
    }

    /**
     * Returns a Domain for the UUID, if available.
     *
     * The domain field is only relevant to version 2 UUIDs.
     */
    private function getLocalDomainFromUuid(string $uuid, ?Format $format): ?DceDomain
    {
        return match ($format) {
            Format::Bytes => DceDomain::tryFrom($this->getLocalDomainFromBytes($uuid)),
            Format::Hex => DceDomain::tryFrom((int) hexdec(substr($uuid, 18, 2))),
            Format::String => DceDomain::tryFrom((int) hexdec(substr($uuid, 21, 2))),
            default => null,
        };
    }

    /**
     * Returns the UUID variant, if available.
     */
    private function getVariantFromUuid(string $uuid, ?Format $format): ?Variant
    {
        return match ($format) {
            Format::Bytes => $this->determineVariant($this->getVariantFromBytes($uuid)),
            Format::Hex => $this->determineVariant((int) hexdec($uuid[16])),
            Format::String => $this->determineVariant((int) hexdec($uuid[19])),
            default => null,
        };
    }

    /**
     * Returns the UUID version, if available.
     */
    private function getVersionFromUuid(string $uuid, ?Format $format, bool $guid = false): ?int
    {
        return match ($format) {
            Format::Bytes => $this->getVersionFromBytes($uuid, $guid),
            Format::Hex => (int) hexdec($uuid[12]),
            Format::String => (int) hexdec($uuid[14]),
            default => null,
        };
    }

    /**
     * Returns true if the given string standard, hexadecimal, or byte representation of a UUID has a valid format.
     *
     * @phpstan-assert-if-true non-empty-string $uuid
     */
    private function hasValidFormat(string $uuid, ?Format $format): bool
    {
        $length = strlen($uuid);

        return match ($format) {
            Format::Bytes => $length === 16,
            Format::Hex => $length === 32 && strspn($uuid, Mask::HEX) === $length,
            Format::String => $length === 36 && $this->isValidStringLayout($uuid, Mask::HEX),
            default => false,
        };
    }

    /**
     * Returns true if the given standard string, hexadecimal, or byte representation of a UUID is a Max UUID.
     */
    private function isMax(string $uuid, ?Format $format): bool
    {
        return match ($format) {
            Format::Bytes => $uuid === "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
            Format::Hex => strtolower($uuid) === 'ffffffffffffffffffffffffffffffff',
            Format::String => strtolower($uuid) === 'ffffffff-ffff-ffff-ffff-ffffffffffff',
            default => false,
        };
    }

    /**
     * Returns true if the given standard string, hexadecimal, or byte representation of a UUID is a Nil UUID.
     */
    private function isNil(string $uuid, ?Format $format): bool
    {
        return match ($format) {
            Format::Bytes => $uuid === "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
            Format::Hex => $uuid === '00000000000000000000000000000000',
            Format::String => $uuid === '00000000-0000-0000-0000-000000000000',
            default => false,
        };
    }

    /**
     * Validates a UUID according to the RFC 9562 layout.
     *
     * The UUID may be in standard string, hexadecimal, or byte representation.
     *
     * @phpstan-assert-if-true non-empty-string $uuid
     */
    private function isValid(string $uuid, ?Format $format): bool
    {
        return $this->hasValidFormat($uuid, $format)
            && $this->getVariantFromUuid($uuid, $format) === Variant::Rfc
            && $this->getVersionFromUuid($uuid, $format) === $this->getVersion()->value;
    }

    /**
     * Returns true if the UUID is a valid standard string representation.
     *
     * @param string $mask A character mask used to validate the UUID string.
     *
     * @phpstan-assert-if-true non-empty-string $uuid
     */
    private function isValidStringLayout(string $uuid, string $mask): bool
    {
        $format = explode('-', $uuid);

        return count($format) === 5
            && strlen($format[0]) === 8
            && strlen($format[1]) === 4
            && strlen($format[2]) === 4
            && strlen($format[3]) === 4
            && strlen($format[4]) === 12
            && strspn($uuid, "-$mask") === Format::String->value;
    }

    private function getLocalDomainFromBytes(string $bytes): int
    {
        /** @var int[] $parts */
        $parts = unpack('n', "\x00" . $bytes[9]);

        // If $parts[1] is not set, return an integer that won't exist in Domain, so that Domain::tryFrom() returns null.
        return $parts[1] ?? PHP_INT_MIN;
    }

    private function getVariantFromBytes(string $bytes): int
    {
        /** @var positive-int[] $parts */
        $parts = unpack('n4', $bytes, 8);

        return $parts[1] >> 12;
    }

    private function getVersionFromBytes(string $bytes, bool $guid): int
    {
        /** @var positive-int[] $parts */
        $parts = unpack('n*', $bytes, $guid ? 7 : 6);

        return ($parts[1] & 0xf000) >> 12;
    }
}
