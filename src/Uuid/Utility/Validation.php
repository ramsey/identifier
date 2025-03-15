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
 * This internal trait provides common validation functionality for RFC 9562 UUIDs
 *
 * @internal
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
     * Given an integer value of the variant bits, this returns the variant
     * associated with those bits
     *
     * @link https://www.rfc-editor.org/rfc/rfc9562#section-4.1 RFC 9562, section 4.1. Variant Field
     */
    private function determineVariant(int $value): Variant
    {
        return match (true) {
            $value >> 1 === 7 => Variant::Future,
            $value >> 1 === 6 => Variant::Microsoft,
            $value >> 2 === 2 => Variant::Rfc9562,
            default => Variant::Ncs,
        };
    }

    /**
     * Returns a Domain for the UUID, if available
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
     * Returns the UUID variant, if available
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
     * Returns the UUID version, if available
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
     * Returns true if the given string standard, hexadecimal, or bytes
     * representation of a UUID has a valid format
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
     * Returns true if the given string standard, hexadecimal, or bytes
     * representation of a UUID is a Max UUID
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
     * Returns true if the given string standard, hexadecimal, or bytes
     * representation of a UUID is a Nil UUID
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
     * Validates a UUID according to the RFC 9562 layout
     *
     * The UUID may be in string standard, hexadecimal, or bytes representation.
     */
    private function isValid(string $uuid, ?Format $format): bool
    {
        return $this->hasValidFormat($uuid, $format)
            && $this->getVariantFromUuid($uuid, $format) === Variant::Rfc9562
            && $this->getVersionFromUuid($uuid, $format) === $this->getVersion()->value;
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

        // If $parts[1] is not set, return an integer that won't
        // exist in Domain, so that Domain::tryFrom() returns null.
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
