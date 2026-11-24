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

namespace Ramsey\Identifier\Ulid\Internal;

use Brick\Math\BigInteger;
use DateTimeImmutable;
use Identifier\Identifier;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Internal\ComparesBySortKey;
use Ramsey\Identifier\Internal\IntegerString;
use Ramsey\Identifier\Ulid;
use Ramsey\Identifier\Uuid\Internal\Binary;
use Ramsey\Identifier\Uuid\UntypedUuid;
use Ramsey\Identifier\Uuid\UuidV7;
use Ramsey\Identifier\Uuid\Version;

use function abs;
use function bin2hex;
use function hex2bin;
use function intdiv;
use function is_string;
use function sprintf;
use function str_pad;
use function strlen;
use function strtolower;
use function strtoupper;
use function strtr;
use function substr;
use function unpack;

use const STR_PAD_LEFT;

/**
 * Provides common methods for ULIDs
 *
 * @internal Not intended for use outside ramsey/identifier; may change without
 *     notice.
 */
trait Standard
{
    use ComparesBySortKey;
    use Validation {
        isValid as private isValidUlid;
    }

    /**
     * @link https://www.crockford.com/base32.html Crockford base-32 specification
     */
    private const CROCKFORD32_ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    private readonly Format $format;

    /**
     * @var non-empty-string
     */
    private readonly string $ulid;

    /**
     * @param non-empty-string $ulid A representation of a ULID in either
     *     Crockford base-32 or byte form
     *
     * @throws InvalidArgument
     */
    public function __construct(string $ulid)
    {
        $original = $ulid;
        $format = Format::tryFrom(strlen($ulid));

        if ($format === Format::Ulid) {
            $ulid = strtr($ulid, ...self::DECODE_SYMBOLS);
        }

        if ($format === null || !$this->isValid($ulid, $format)) {
            throw new InvalidArgument(sprintf('Invalid ULID: "%s"', $original));
        }

        $this->format = $format;
        $this->ulid = $ulid;
    }

    /**
     * @return array{ulid: non-empty-string}
     */
    public function __serialize(): array
    {
        return ['ulid' => $this->ulid];
    }

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return $this->getFormat(Format::Ulid);
    }

    /**
     * @param array{ulid?: non-empty-string} $data
     *
     * @throws InvalidArgument if the data has no string for the key "ulid", or
     *     the string is not a valid identifier
     */
    public function __unserialize(array $data): void
    {
        if (!is_string($data['ulid'] ?? null)) {
            throw new InvalidArgument('The serialized data must have a string value for "ulid"');
        }

        $this->__construct($data['ulid']);
    }

    public function getDateTime(): DateTimeImmutable
    {
        $bytes = $this->getFormat(Format::Bytes);

        /** @var int[] $parts */
        $parts = unpack('J', "\x00\x00" . substr($bytes, 0, 6));

        $timestamp = sprintf(
            '%d.%03d',
            intdiv($parts[1], 1000),
            abs($parts[1]) % 1000,
        );

        return new DateTimeImmutable('@' . $timestamp);
    }

    /**
     * Returns a string of bytes that places the identifier in order among other
     * identifiers
     *
     * @return non-empty-string
     */
    public function getSortKey(): string
    {
        return $this->getFormat(Format::Bytes);
    }

    /**
     * @return non-empty-string
     */
    public function jsonSerialize(): string
    {
        return $this->getFormat(Format::Ulid);
    }

    /**
     * @return non-empty-string
     */
    public function toBytes(): string
    {
        return $this->getFormat(Format::Bytes);
    }

    /**
     * @return non-empty-string
     */
    public function toHexadecimal(): string
    {
        return $this->getFormat(Format::Hex);
    }

    /**
     * @return int<0, max> | numeric-string
     */
    public function toInteger(): int | string
    {
        /** @var numeric-string $ulidInteger */
        $ulidInteger = $this->getFormat(null);

        if (IntegerString::fitsNativeInteger($ulidInteger)) {
            /** @var int<0, max> */
            return (int) $ulidInteger;
        }

        return $ulidInteger;
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->getFormat(Format::Ulid);
    }

    /**
     * Returns this ULID as a binary-equal UUID instance
     *
     * This method returns an {@see UntypedUuid} instance that has the same
     * bytes as the ULID from which it was created. As a result, the UUID might
     * not be a valid RFC 9562 variant UUID. If this is the case, the UUID
     * returned from {@see UntypedUuid::toTypedUuid()} will be of the type
     * {@see \Ramsey\Identifier\Uuid\NonstandardUuid NonstandardUuid}.
     */
    public function toUuid(): UntypedUuid
    {
        return new UntypedUuid($this->toBytes());
    }

    /**
     * Returns this ULID as a version 7 UUID instance
     *
     * This method differs from {@see self::toUuid()} in that the bytes of the
     * resulting UUID might be different from the original ULID. The two values
     * might not be binary-equal!
     *
     * A version 7 UUID has its version and its variant in six bits that a ULID
     * uses for other things, and this method writes them there.
     *
     * A ULID created by {@see \Ramsey\Identifier\Ulid\UlidFactory UlidFactory}
     * with its default bytes generator has zeros in those six bits, so the
     * version 7 UUID has the same timestamp, the same fraction of the
     * millisecond, and the same counter. Any other ULID may have other values
     * in those bits, and the UUID does not keep them.
     */
    public function toUuidV7(): UuidV7
    {
        return new UuidV7(Binary::applyVersionAndVariant($this->toBytes(), Version::UnixTime));
    }

    private function isSameKind(Identifier $other): bool
    {
        return $other instanceof Ulid;
    }

    /**
     * @return non-empty-string | null
     */
    private function sortKeyFromString(string $value): ?string
    {
        if (strlen($value) !== Format::Ulid->value) {
            return null;
        }

        $value = strtr($value, ...self::DECODE_SYMBOLS);

        // MaxUlid and NilUlid replace isValid() with a check for their own
        // value, but they compare with any ULID.
        if (!$this->isValidUlid($value, Format::Ulid)) {
            return null;
        }

        return $this->convert($value, Format::Ulid, Format::Bytes);
    }

    /**
     * @return non-empty-string
     */
    private function getFormat(?Format $formatToReturn): string
    {
        return $this->convert($this->ulid, $this->format, $formatToReturn);
    }

    /**
     * Converts a ULID from one format to another
     *
     * @param non-empty-string $ulid A valid ULID in the format `$formatOfUlid`
     * @param Format | null $formatToReturn The format to return, or null for
     *     a string of decimal digits
     *
     * @return non-empty-string
     */
    private function convert(string $ulid, Format $formatOfUlid, ?Format $formatToReturn): string
    {
        /** @var non-empty-string */
        return match ($formatToReturn) {
            Format::Bytes => match ($formatOfUlid) {
                Format::Bytes => $ulid,
                Format::Hex => (string) hex2bin($ulid),
                Format::Ulid => str_pad(
                    BigInteger::fromArbitraryBase(strtoupper($ulid), self::CROCKFORD32_ALPHABET)->toBytes(false),
                    16,
                    "\x00",
                    STR_PAD_LEFT,
                ),
            },
            Format::Hex => match ($formatOfUlid) {
                Format::Bytes => bin2hex($ulid),
                Format::Hex => strtolower($ulid),
                Format::Ulid => sprintf(
                    '%032s',
                    BigInteger::fromArbitraryBase(strtoupper($ulid), self::CROCKFORD32_ALPHABET)->toBase(16),
                ),
            },
            Format::Ulid => match ($formatOfUlid) {
                Format::Bytes => sprintf(
                    '%026s',
                    BigInteger::fromBytes($ulid, false)->toArbitraryBase(self::CROCKFORD32_ALPHABET),
                ),
                Format::Hex => sprintf(
                    '%026s',
                    BigInteger::fromBase($ulid, 16)->toArbitraryBase(self::CROCKFORD32_ALPHABET),
                ),
                Format::Ulid => strtoupper($ulid),
            },
            null => match ($formatOfUlid) {
                Format::Bytes => BigInteger::fromBytes($ulid, false)->toBase(10),
                Format::Hex => BigInteger::fromBase($ulid, 16)->toBase(10),
                Format::Ulid => BigInteger::fromArbitraryBase(strtoupper($ulid), self::CROCKFORD32_ALPHABET)
                    ->toBase(10),
            },
        };
    }
}
