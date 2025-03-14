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

use Brick\Math\BigInteger;
use DateTimeImmutable;
use Ramsey\Identifier\Exception\BadMethodCall;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\NodeBasedUuid;
use Ramsey\Identifier\TimeBasedUuid;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Utility\Binary;
use Ramsey\Identifier\Uuid\Utility\Format;
use Ramsey\Identifier\Uuid\Utility\NodeBased;
use Ramsey\Identifier\Uuid\Utility\Standard;
use Ramsey\Identifier\Uuid\Utility\TimeBased;

use function assert;
use function bin2hex;
use function hex2bin;
use function hexdec;
use function sprintf;
use function str_replace;
use function strlen;
use function substr;

/**
 * Microsoft GUIDs are identical to RFC 9562 UUIDs, except for the endianness of
 * the most-significant 8 bytes when stored or transmitted in binary form. While
 * RFC 9562 UUIDs use "network," or big-endian, byte order for all 16 bytes,
 * Microsoft GUIDs store the most-significant 8 bytes in "native," or
 * little-endian, byte order and the least-significant 8 bytes in network byte
 * order.
 *
 * For backwards compatibility, Microsoft GUIDs may be encoded using the
 * "reserved Microsoft" {@see Variant variant} bits. However, in practice, they
 * are often encoded as standard RFC 9562 variant UUIDs and stored using the
 * Microsoft GUID byte order. Since it is impossible to determine whether the
 * bytes for an RFC 9562 UUID are stored in the standard network byte order or
 * using Microsoft's GUID byte order, applications using Microsoft GUIDs must
 * take care to keep track of this information or, alternately, encode the UUIDs
 * as "reserved Microsoft" variant UUIDs.
 *
 * > [!WARNING]
 * > This class supports both "reserved Microsoft" and RFC 9562 variants.
 * > Please understand that, if using this class with RFC 9562 variants,
 * > bytes will be treated as if they are in Microsoft's GUID byte order. This
 * > might cause problems if your application receives UUID bytes stored or
 * > transmitted in network byte order.
 *
 * There is no difference between the string and hexadecimal representations of
 * Microsoft GUIDs and RFC 9562 UUIDs.
 *
 * @link https://learn.microsoft.com/en-us/windows/win32/api/guiddef/ns-guiddef-guid#remarks Micosoft documentation remarks on GUIDs
 * @link https://learn.microsoft.com/en-us/dotnet/api/system.guid.tobytearray?view=net-7.0#remarks Microsoft documentation remarks on GUID byte order
 */
final readonly class MicrosoftGuid implements NodeBasedUuid, TimeBasedUuid
{
    use Standard {
        compareTo as private baseCompareTo;
        getFormat as private baseGetFormat;
    }
    use NodeBased {
        getNode as private baseGetNode;
    }
    use TimeBased {
        getDateTime as private baseGetDateTime;
    }

    private Binary $binary;
    private ?Variant $variant;
    private ?Version $version;

    /**
     * @throws InvalidArgument
     */
    public function __construct(private string $uuid)
    {
        $this->format = Format::tryFrom(strlen($this->uuid));
        $this->variant = $this->getVariantFromUuid($this->uuid, $this->format);
        $this->version = Version::tryFrom((int) $this->getVersionFromUuid($this->uuid, $this->format, true));

        if (!$this->isValid($this->uuid, $this->format)) {
            throw new InvalidArgument(sprintf('Invalid Microsoft GUID: "%s"', $this->uuid));
        }

        $this->binary = new Binary();
    }

    /**
     * @return array{guid: string}
     */
    public function __serialize(): array
    {
        return ['guid' => $this->uuid];
    }

    /**
     * @param array{guid: string} $data
     *
     * @throws InvalidArgument
     */
    public function __unserialize(array $data): void
    {
        assert(isset($data['guid']), "'guid' is not set in serialized data");

        $this->__construct($data['guid']);
    }

    public function compareTo(mixed $other): int
    {
        if ($other instanceof MicrosoftGuid) {
            return $this->toBytes() <=> $other->toBytes();
        }

        // We need to compare with strings here, since Microsoft GUID bytes
        // are in a different order than UUID bytes.
        if ($other instanceof Uuid) {
            return $this->toString() <=> $other->toString();
        }

        return $this->baseCompareTo($other);
    }

    /**
     * @throws BadMethodCall when called on a GUID that does not support
     *     date-time values
     */
    public function getDateTime(): DateTimeImmutable
    {
        return match ($this->getVersion()) {
            Version::GregorianTime, Version::DceSecurity,
            Version::ReorderedGregorianTime, Version::UnixTime => $this->baseGetDateTime(),
            default => throw new BadMethodCall(sprintf(
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
     * @throws BadMethodCall when called on a GUID that does not support local
     *     identifier values
     */
    public function getLocalIdentifier(): int
    {
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

    public function getVariant(): Variant
    {
        assert($this->variant !== null);

        return $this->variant;
    }

    public function getVersion(): Version
    {
        assert($this->version !== null);

        return $this->version;
    }

    /**
     * Returns an RFC 9562 variant version of this Microsoft GUID
     *
     * The new UUID returned will be of the RFC 9562 variant. If this GUID is
     * of the "reserved Microsoft" variant, this means some bits will change,
     * and the two values will not be equal.
     */
    public function toRfc(): UuidV1 | UuidV2 | UuidV3 | UuidV4 | UuidV5 | UuidV6 | UuidV7 | UuidV8
    {
        $bytes = $this->swapBytes($this->toBytes());
        $bytes = $this->binary->applyVersionAndVariant($bytes, $this->getVersion());

        return match ($this->getVersion()) {
            Version::V1 => new UuidV1($bytes),
            Version::V2 => new UuidV2($bytes),
            Version::V3 => new UuidV3($bytes),
            Version::V4 => new UuidV4($bytes),
            Version::V5 => new UuidV5($bytes),
            Version::V6 => new UuidV6($bytes),
            Version::V7 => new UuidV7($bytes),
            Version::V8 => new UuidV8($bytes),
        };
    }

    /**
     * @return non-empty-string
     */
    protected function getFormat(?Format $formatToReturn, ?string $uuid = null): string
    {
        $formatOfUuid = null;
        if ($uuid !== null) {
            $formatOfUuid = Format::tryFrom(strlen($uuid));
        }

        $formatOfUuid ??= $this->format;
        $uuid ??= $this->uuid;

        if ($formatOfUuid === null) {
            throw new InvalidArgument('Invalid UUID format');
        }

        /** @var non-empty-string */
        return match ($formatToReturn) {
            Format::Bytes => match ($formatOfUuid) {
                Format::Bytes => $uuid,
                Format::Hex => $this->swapBytes((string) hex2bin($uuid)),
                Format::String => $this->swapBytes((string) hex2bin(str_replace('-', '', $uuid))),
            },
            Format::Hex => match ($formatOfUuid) {
                Format::Bytes => bin2hex($this->swapBytes($uuid)),
                Format::Hex, Format::String => $this->baseGetFormat(Format::Hex, $uuid),
            },
            Format::String => match ($formatOfUuid) {
                Format::Bytes => $this->toStringFromHex(bin2hex($this->swapBytes($uuid))),
                Format::Hex, Format::String => $this->baseGetFormat(Format::String, $uuid),
            },
            default => match ($formatOfUuid) {
                Format::Bytes => BigInteger::fromBytes($this->swapBytes($uuid), false)->toBase(10),
                Format::Hex, Format::String => $this->baseGetFormat(null, $uuid),
            },
        };
    }

    private function isValid(string $uuid, ?Format $format): bool
    {
        // We'll assume RFC 9562 as valid GUIDs and trust that, if a developer
        // is using MicrosoftGuid, it's because they know the bytes of the time
        // fields are stored in "native" (little-endian) byte order.
        return $this->hasValidFormat($uuid, $format)
            && ($this->variant === Variant::ReservedMicrosoft || $this->variant === Variant::Rfc9562)
            && $this->version !== null;
    }

    /**
     * Swaps the bytes in the first three fields of a GUID to/from
     * network byte order
     *
     * @link https://en.wikipedia.org/w/index.php?title=Universally_unique_identifier&oldid=1116582443#Encoding Encoding
     *
     * @return non-empty-string
     */
    private function swapBytes(string $bytes): string
    {
        if (strlen($bytes) !== 16) {
            throw new BadMethodCall('swapBytes() called out of context');
        }

        return $bytes[3] . $bytes[2] . $bytes[1] . $bytes[0]
            . $bytes[5] . $bytes[4]
            . $bytes[7] . $bytes[6]
            . substr($bytes, 8);
    }
}
