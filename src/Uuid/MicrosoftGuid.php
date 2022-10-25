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

use DateTimeImmutable;
use JsonSerializable;
use Ramsey\Identifier\Exception\BadMethodCall;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\NodeBasedUuidIdentifier;
use Ramsey\Identifier\Uuid\Utility\Binary;
use Ramsey\Identifier\Uuid\Utility\Format;
use Ramsey\Identifier\Uuid\Utility\NodeBasedUuid;
use Ramsey\Identifier\UuidIdentifier;

use function assert;
use function bin2hex;
use function hex2bin;
use function hexdec;
use function sprintf;
use function str_replace;
use function strcmp;
use function strlen;
use function substr;

/**
 * Microsoft GUIDs are identical to RFC 4122 UUIDs, except for the endianness of
 * the most-significant 8 bytes when stored or transmitted in binary form. While
 * RFC 4122 UUIDs use "network," or big-endian, byte order for all 16 bytes,
 * Microsoft GUIDs store the most-significant 8 bytes in "native," or
 * little-endian, byte order and the least-significant 8 bytes in network byte
 * order.
 *
 * For backwards compatibility, Microsoft GUIDs may be encoded using the
 * "reserved Microsoft" {@see \Ramsey\Identifier\Uuid\Variant variant} bits.
 * However, in practice, they are often encoded as standard RFC 4122 variant
 * UUIDs and stored using the Microsoft GUID byte order. Since it is impossible
 * to determine whether the bytes for an RFC 4122 UUID are stored in the
 * standard network byte order or using Microsoft's GUID byte order,
 * applications using Microsoft GUIDs must take care to keep track of this
 * information or, alternately, encode the UUIDs as "reserved Microsoft" variant
 * UUIDs.
 *
 * ⚠️ WARNING: This class supports both "reserved Microsoft" and RFC 4122
 * variants. Please understand that, if using this class with RFC 4122 variants,
 * bytes will be treated as if they are in Microsoft's GUID byte order. This
 * might cause problems if your application receives UUID bytes stored or
 * transmitted in network byte order.
 *
 * There is no difference between the string and hexadecimal representations of
 * Microsoft GUIDs and RFC 4122 UUIDs.
 *
 * @psalm-immutable
 */
final class MicrosoftGuid implements JsonSerializable, NodeBasedUuidIdentifier
{
    use NodeBasedUuid {
        compareTo as private baseCompareTo;
        getDateTime as private baseGetDateTime;
        getFormat as private baseGetFormat;
        getNode as private baseGetNode;
    }

    private readonly Binary $binary;
    private readonly ?Variant $variant;
    private readonly ?Version $version;

    /**
     * @throws InvalidArgument
     */
    public function __construct(private readonly string $uuid)
    {
        $this->format = strlen($this->uuid);
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
            return strcmp($this->toBytes(), $other->toBytes());
        }

        // We need to compare with strings here, since Microsoft GUID bytes
        // are in a different order than UUID bytes.
        if ($other instanceof UuidIdentifier) {
            /** @psalm-suppress ImpureMethodCall UuidIdentifier doesn't make any purity guarantees. */
            return strcmp($this->toString(), $other->toString());
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
            Version::DceSecurity => (int) hexdec(substr($this->getFormat(Format::FORMAT_STRING), 0, 8)),
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
     * Returns an RFC 4122 variant version of this Microsoft GUID
     *
     * The new UUID returned will be of the RFC 4122 variant. If this GUID is
     * of the "reserved Microsoft" variant, this means some bits will change,
     * and the two values will not be equal.
     */
    public function toRfc4122(): UuidV1 | UuidV2 | UuidV3 | UuidV4 | UuidV5 | UuidV6 | UuidV7 | UuidV8
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
     *
     * @psalm-param 36 | 32 | 16 $formatToReturn
     */
    protected function getFormat(int $formatToReturn, ?string $uuid = null): string
    {
        /** @psalm-var 36 | 32 | 16 $formatOfUuid */
        $formatOfUuid = $uuid ? strlen($uuid) : $this->format;
        $uuid ??= $this->uuid;

        /** @var non-empty-string */
        return match ($formatToReturn) {
            Format::FORMAT_STRING => match ($formatOfUuid) {
                Format::FORMAT_STRING, Format::FORMAT_HEX => $this->baseGetFormat(Format::FORMAT_STRING, $uuid),
                Format::FORMAT_BYTES => $this->toStringFromHex(bin2hex($this->swapBytes($uuid))),
            },
            Format::FORMAT_HEX => match ($formatOfUuid) {
                Format::FORMAT_STRING, Format::FORMAT_HEX => $this->baseGetFormat(Format::FORMAT_HEX, $uuid),
                Format::FORMAT_BYTES => bin2hex($this->swapBytes($uuid)),
            },
            Format::FORMAT_BYTES => match ($formatOfUuid) {
                Format::FORMAT_STRING => $this->swapBytes((string) hex2bin(str_replace('-', '', $uuid))),
                Format::FORMAT_HEX => $this->swapBytes((string) hex2bin($uuid)),
                Format::FORMAT_BYTES => $uuid,
            },
        };
    }

    private function isValid(string $uuid, int $format): bool
    {
        // We'll assume RFC 4122 as valid GUIDs and trust that, if a developer
        // is using MicrosoftGuid, it's because they know the bytes of the time
        // fields are stored in "native" (little-endian) byte order.
        return $this->hasValidFormat($uuid, $format)
            && ($this->variant === Variant::ReservedMicrosoft || $this->variant === Variant::Rfc4122)
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
            throw new BadMethodCall('swapBytes() called out of context'); // @codeCoverageIgnore
        }

        /** @var non-empty-string */
        return $bytes[3] . $bytes[2] . $bytes[1] . $bytes[0]
            . $bytes[5] . $bytes[4]
            . $bytes[7] . $bytes[6]
            . substr($bytes, 8);
    }
}
