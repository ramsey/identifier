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

namespace Ramsey\Identifier\Uuid;

use Identifier\Uuid\Variant;
use Identifier\Uuid\Version;

use function count;
use function explode;
use function hexdec;
use function strlen;
use function strspn;
use function substr;
use function unpack;

/**
 * This internal trait provides common validation functionality for RFC 4122 UUIDs
 *
 * @internal
 *
 * @psalm-immutable
 */
trait Validation
{
    /**
     * Returns the Version enum for the UUID type represented by the class
     * using this trait
     *
     * We use this in {@see self::isValid()} to determine whether the UUID is
     * valid for the type the class represents.
     */
    abstract protected function getVersion(): Version;

    /**
     * Returns the UUID variant, if available
     */
    private function getVariantFromUuid(string $uuid): ?Variant
    {
        return match (strlen($uuid)) {
            36 => $this->determineVariant((int) hexdec(substr($uuid, 19, 1))),
            32 => $this->determineVariant((int) hexdec(substr($uuid, 16, 1))),
            16 => $this->determineVariant(
                (
                    function (string $uuid): int {
                        /** @var positive-int[] $parts */
                        $parts = unpack('n*', $uuid, 8);

                        return $parts[1] >> 12;
                    }
                )(
                    $uuid,
                ),
            ),
            default => null,
        };
    }

    /**
     * Given an integer value of the variant bits, this returns the variant
     * associated with those bits
     *
     * @link https://datatracker.ietf.org/doc/html/rfc4122#section-4.1.1 Variant
     */
    private function determineVariant(int $value): Variant
    {
        return match (true) {
            $value >> 1 === 7 => Variant::ReservedFuture,
            $value >> 1 === 6 => Variant::ReservedMicrosoft,
            $value >> 2 === 2 => Variant::Rfc4122,
            default => Variant::ReservedNcs,
        };
    }

    /**
     * Returns the UUID version, if available
     */
    private function getVersionFromUuid(string $uuid): ?int
    {
        return match (strlen($uuid)) {
            36 => (int) hexdec(substr($uuid, 14, 1)),
            32 => (int) hexdec(substr($uuid, 12, 1)),
            16 => (static function (string $uuid): int {
                /** @var positive-int[] $parts */
                $parts = unpack('n*', $uuid, 6);

                return ($parts[1] & 0xf000) >> 12;
            })($uuid),
            default => null,
        };
    }

    /**
     * Returns true if the given string standard, hexadecimal, or bytes
     * representation of a UUID has a valid format
     */
    private function hasValidFormat(string $uuid): bool
    {
        $mask = '0123456789abcdefABCDEF';

        return match (strlen($uuid)) {
            36 => $this->isValidStringLayout($uuid, $mask),
            32 => strspn($uuid, $mask) === 32,
            16 => true,
            default => false,
        };
    }

    /**
     * Returns true if the given string standard, hexadecimal, or bytes
     * representation of a UUID is a Max UUID
     */
    private function isMax(string $uuid): bool
    {
        // We support uppercase, lowercase, and mixed case.
        $mask = 'fF';

        return match (strlen($uuid)) {
            36 => $this->isValidStringLayout($uuid, $mask),
            32 => strspn($uuid, $mask) === 32,
            16 => $uuid === "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
            default => false,
        };
    }

    /**
     * Returns true if the given string standard, hexadecimal, or bytes
     * representation of a UUID is a Nil UUID
     */
    private function isNil(string $uuid): bool
    {
        return match (strlen($uuid)) {
            36 => $uuid === '00000000-0000-0000-0000-000000000000',
            32 => $uuid === '00000000000000000000000000000000',
            16 => $uuid === "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
            default => false,
        };
    }

    /**
     * Validates a UUID according to the RFC 4122 layout
     *
     * The UUID may be in string standard, hexadecimal, or bytes representation.
     */
    private function isValid(string $uuid): bool
    {
        return $this->hasValidFormat($uuid)
            && $this->getVariantFromUuid($uuid) === Variant::Rfc4122
            && $this->getVersionFromUuid($uuid) === $this->getVersion()->value;
    }

    /**
     * Returns true if the UUID is a valid string standard representation
     *
     * @param string $mask Typically a hexadecimal mask but may also be used to
     *     validate alternate masks, such as with Max UUIDs
     */
    private function isValidStringLayout(string $uuid, string $mask): bool
    {
        $format = explode('-', $uuid);

        return count($format) === 5
            && strspn($format[0], $mask) === 8
            && strspn($format[1], $mask) === 4
            && strspn($format[2], $mask) === 4
            && strspn($format[3], $mask) === 4
            && strspn($format[4], $mask) === 12;
    }
}
