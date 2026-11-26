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

namespace Ramsey\Identifier\Uuid;

use Brick\Math\BigInteger;
use DateTimeImmutable;
use Override;
use Ramsey\Identifier\Exception\BadMethodCall;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotConvertible;
use Ramsey\Identifier\NodeBasedUuid;
use Ramsey\Identifier\TimeBasedUuid;
use Ramsey\Identifier\Uuid\Internal\Binary;
use Ramsey\Identifier\Uuid\Internal\Format;
use Ramsey\Identifier\Uuid\Internal\NodeBased;
use Ramsey\Identifier\Uuid\Internal\Standard;
use Ramsey\Identifier\Uuid\Internal\TimeBased;

use function bin2hex;
use function hexdec;
use function is_string;
use function sprintf;
use function strlen;
use function substr;

/**
 * Microsoft GUIDs are identical to RFC 9562 UUIDs, except for the endianness of
 * the most-significant 8 bytes when stored or transmitted in binary form
 *
 * While RFC 9562 UUIDs use "network," or big-endian, byte order for all 16
 * bytes, Microsoft GUIDs store the most-significant 8 bytes in "native," or
 * little-endian, byte order and the least-significant 8 bytes in network byte
 * order.
 *
 * For backwards compatibility, Microsoft GUIDs may be encoded using the
 * "reserved Microsoft" {@see Variant variant} bits. However, in practice, they
 * are often encoded as standard RFC 9562 variant UUIDs and stored using the
 * Microsoft GUID byte order. Since it is impossible to determine whether the
 * UUID bytes are stored in network byte order or using Microsoft's GUID byte
 * order, applications must keep track of this information or encode the UUIDs
 * as "reserved Microsoft" variant UUIDs.
 *
 * > [!WARNING]
 * > This class supports both "reserved Microsoft" and RFC 9562 variants. Please
 * > understand that, if using this class with RFC 9562 variants, bytes will be
 * > treated as if they are in Microsoft's GUID byte order. This might cause
 * > problems if your application receives UUID bytes stored or transmitted in
 * > network byte order.
 *
 * There is no difference between the string and hexadecimal representations of
 * Microsoft GUIDs and standard UUIDs.
 *
 * @link https://learn.microsoft.com/en-us/dotnet/api/system.guid.tobytearray?view=net-7.0 Microsoft documentation remarks on GUID byte order
 */
final readonly class MicrosoftGuid implements NodeBasedUuid, TimeBasedUuid
{
    use Standard {
        getFormat as private baseGetFormat;
    }
    use NodeBased {
        getNode as private baseGetNode;
    }
    use TimeBased {
        getDateTime as private baseGetDateTime;
    }

    private Variant $variant;
    private Version $version;

    /**
     * @param non-empty-string $uuid A representation of the GUID as a string
     *     with dashes, hexadecimal, or byte string
     *
     * @throws InvalidArgument
     */
    public function __construct(private string $uuid)
    {
        $format = Format::tryFrom(strlen($this->uuid));
        $variant = null;
        $version = null;

        if ($format !== null && $this->hasValidFormat($this->uuid, $format)) {
            $variant = $this->getVariantFromUuid($this->uuid, $format);
            $version = Version::tryFrom($this->getVersionFromUuid($this->uuid, $format, true));
        }

        // We'll assume RFC 9562 as valid GUIDs and trust that, if a developer
        // is using MicrosoftGuid, it's because they know the bytes of the time
        // fields are stored in "native" (little-endian) byte order.
        if (
            $format === null
            || $version === null
            || ($variant !== Variant::Microsoft && $variant !== Variant::Rfc)
        ) {
            throw new InvalidArgument(sprintf('Invalid Microsoft GUID: "%s"', $this->uuid));
        }

        $this->format = $format;
        $this->variant = $variant;
        $this->version = $version;
    }

    /**
     * @return array{guid: non-empty-string}
     */
    public function __serialize(): array
    {
        return ['guid' => $this->uuid];
    }

    /**
     * @param array{guid?: non-empty-string} $data
     *
     * @throws InvalidArgument if the data has no string for the key "guid", or
     *     the string is not a valid identifier
     */
    public function __unserialize(array $data): void
    {
        if (!is_string($data['guid'] ?? null)) {
            throw new InvalidArgument('The serialized data must have a string value for "guid"');
        }

        $this->__construct($data['guid']);
    }

    /**
     * @throws NotConvertible when called on a GUID that does not support
     *     date-time values
     */
    #[Override]
    public function getDateTime(): DateTimeImmutable
    {
        return match ($this->getVersion()) {
            Version::GregorianTime, Version::DceSecurity,
            Version::ReorderedGregorianTime, Version::UnixTime => $this->baseGetDateTime(),
            default => throw new NotConvertible(sprintf(
                'Version %d GUIDs do not contain date-time values',
                $this->getVersion()->value,
            )),
        };
    }

    /**
     * Returns the local domain to which the local identifier belongs
     *
     * @see UuidV2::getLocalDomain()
     *
     * @throws BadMethodCall when called on a GUID that does not support local
     *     domain values
     */
    public function getLocalDomain(): DceDomain
    {
        /** @var DceDomain */
        return match ($this->getVersion()) {
            Version::DceSecurity => $this->getLocalDomainFromUuid($this->uuid, $this->format),
            default => throw new BadMethodCall(sprintf(
                'Version %d GUIDs do not contain local domain values',
                $this->getVersion()->value,
            )),
        };
    }

    /**
     * Returns an identifier meaningful to the local host where this UUID was
     * created
     *
     * @see UuidV2::getLocalIdentifier()
     *
     * @return int<0, 4294967295>
     *
     * @throws BadMethodCall when called on a GUID that does not support local
     *     identifier values
     */
    public function getLocalIdentifier(): int
    {
        /** @var int<0, 4294967295> */
        return match ($this->getVersion()) {
            Version::DceSecurity => (int) hexdec(substr($this->getFormat(Format::String), 0, 8)),
            default => throw new BadMethodCall(sprintf(
                'Version %d GUIDs do not contain local identifier values',
                $this->getVersion()->value,
            )),
        };
    }

    /**
     * @throws BadMethodCall when called on a GUID that does not support node
     *     values
     */
    #[Override]
    public function getNode(): string
    {
        /** @var non-empty-string */
        return match ($this->getVersion()) {
            Version::GregorianTime, Version::DceSecurity, Version::ReorderedGregorianTime => $this->baseGetNode(),
            default => throw new BadMethodCall(sprintf(
                'Version %d GUIDs do not contain node values',
                $this->getVersion()->value,
            )),
        };
    }

    /**
     * Returns a string of bytes that places the identifier in order among other
     * identifiers
     *
     * The bytes are in RFC 9562 layout, so a GUID sorts with the UUID that has
     * the same string form.
     *
     * @return non-empty-string
     */
    #[Override]
    public function getSortKey(): string
    {
        return $this->swapBytes($this->toBytes());
    }

    #[Override]
    public function getVariant(): Variant
    {
        return $this->variant;
    }

    #[Override]
    public function getVersion(): Version
    {
        return $this->version;
    }

    /**
     * Returns an RFC 9562 variant version of this Microsoft GUID
     *
     * The new UUID returned will be of the RFC 9562 variant. If this GUID is of
     * the "reserved Microsoft" variant, this means some bits will change, and
     * the two values will not be equal.
     */
    public function toRfc(): UuidV1 | UuidV2 | UuidV3 | UuidV4 | UuidV5 | UuidV6 | UuidV7 | UuidV8
    {
        $bytes = $this->swapBytes($this->toBytes());

        return match ($this->getVersion()) {
            Version::V1 => UuidV1::fromRawBytes($bytes),
            Version::V2 => new UuidV2(Binary::applyVersionAndVariant($bytes, Version::V2)),
            Version::V3 => UuidV3::fromRawBytes($bytes),
            Version::V4 => UuidV4::fromRawBytes($bytes),
            Version::V5 => UuidV5::fromRawBytes($bytes),
            Version::V6 => UuidV6::fromRawBytes($bytes),
            Version::V7 => UuidV7::fromRawBytes($bytes),
            Version::V8 => UuidV8::fromRawBytes($bytes),
        };
    }

    /**
     * @return non-empty-string
     */
    private function getFormat(?Format $formatToReturn): string
    {
        $formatOfUuid = $this->format;
        $uuid = $this->uuid;

        /** @var non-empty-string */
        return match ($formatToReturn) {
            Format::Bytes => match ($formatOfUuid) {
                Format::Bytes => $uuid,
                Format::Hex, Format::String => $this->swapBytes($this->baseGetFormat(Format::Bytes)),
            },
            Format::Hex => match ($formatOfUuid) {
                Format::Bytes => bin2hex($this->swapBytes($uuid)),
                Format::Hex, Format::String => $this->baseGetFormat(Format::Hex),
            },
            Format::String => match ($formatOfUuid) {
                Format::Bytes => $this->toStringFromHex(bin2hex($this->swapBytes($uuid))),
                Format::Hex, Format::String => $this->baseGetFormat(Format::String),
            },
            default => match ($formatOfUuid) {
                Format::Bytes => BigInteger::fromBytes($this->swapBytes($uuid), false)->toBase(10),
                Format::Hex, Format::String => $this->baseGetFormat(null),
            },
        };
    }

    /**
     * Swaps the bytes in the first three fields of a GUID to/from network byte
     * order
     *
     * @link https://en.wikipedia.org/w/index.php?title=Universally_unique_identifier&oldid=1116582443#Encoding Encoding
     *
     * @param non-empty-string $bytes
     *
     * @return non-empty-string
     */
    private function swapBytes(string $bytes): string
    {
        return $bytes[3] . $bytes[2] . $bytes[1] . $bytes[0]
            . $bytes[5] . $bytes[4] . $bytes[7] . $bytes[6]
            . substr($bytes, 8);
    }
}
