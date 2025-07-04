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

namespace Ramsey\Identifier\Ulid\Internal;

use Brick\Math\BigInteger;
use DateTimeImmutable;
use Identifier\BytesIdentifier;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotComparable;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Internal\Binary;
use Ramsey\Identifier\Uuid\UuidFactory;
use Ramsey\Identifier\Uuid\UuidV7;
use Ramsey\Identifier\Uuid\Version;
use Stringable;

use function abs;
use function assert;
use function bin2hex;
use function gettype;
use function hex2bin;
use function intdiv;
use function is_scalar;
use function sprintf;
use function str_pad;
use function strcasecmp;
use function strlen;
use function strtolower;
use function strtoupper;
use function strtr;
use function substr;
use function unpack;

use const PHP_INT_MAX;
use const STR_PAD_LEFT;

/**
 * Provides common methods for ULIDs.
 *
 * @internal Not intended for use outside ramsey/identifier; may change without notice.
 */
trait Standard
{
    use Validation;

    /**
     * @link https://www.crockford.com/base32.html Crockford base-32 specification.
     */
    private const CROCKFORD32_ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    private readonly ?Format $format;

    /**
     * @var non-empty-string
     */
    private readonly string $ulid;

    /**
     * @param non-empty-string $ulid A representation of a ULID in either Crockford base-32 or byte form.
     *
     * @throws InvalidArgument
     */
    public function __construct(string $ulid)
    {
        $original = $ulid;
        $this->format = Format::tryFrom(strlen($ulid));

        if ($this->format === Format::Ulid) {
            $ulid = strtr($ulid, ...self::DECODE_SYMBOLS);
        }

        if (!$this->isValid($ulid, $this->format)) {
            throw new InvalidArgument(sprintf('Invalid ULID: "%s"', $original));
        }

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
     * @param array{ulid: non-empty-string} $data
     *
     * @throws InvalidArgument
     */
    public function __unserialize(array $data): void
    {
        assert(isset($data['ulid']), "'ulid' is not set in serialized data");

        $this->__construct($data['ulid']);
    }

    /**
     * @throws NotComparable
     */
    public function compareTo(mixed $other): int
    {
        if ($other instanceof BytesIdentifier) {
            return $this->toBytes() <=> $other->toBytes();
        }

        if ($other === null || is_scalar($other) || $other instanceof Stringable) {
            $other = (string) $other;

            $otherEncoded = strtr($other, ...self::DECODE_SYMBOLS);
            if ($this->isValid($otherEncoded, Format::tryFrom(strlen($other)))) {
                $other = $this->getFormat(Format::Ulid, $otherEncoded);
            }

            return strcasecmp($this->toString(), $other);
        }

        throw new NotComparable(sprintf('Comparison with values of type "%s" is not supported', gettype($other)));
    }

    public function equals(mixed $other): bool
    {
        try {
            return $this->compareTo($other) === 0;
        } catch (NotComparable) {
            return false;
        }
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

        if ($ulidInteger <= PHP_INT_MAX) {
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
     * Returns this ULID as a binary-equal UUID instance.
     *
     * Both ULIDs and UUIDs are 128-bit integers. At the binary level, their bits are identical.
     *
     * This method returns a UUID instance that has the same bytes as the ULID from which it was created. As a result,
     * the UUID might not be a valid RFC 9562 variant UUID. If this is the case, the resulting UUID will be of the type
     * {@see \Ramsey\Identifier\Uuid\NonstandardUuid NonstandardUuid}.
     */
    public function toUuid(): Uuid
    {
        return (new UuidFactory())->createFromBytes($this->toBytes())->toTypedUuid();
    }

    /**
     * Returns this ULID as a version 7 UUID instance.
     *
     * This method differs from {@see self::toUuid()} in that the bytes of the resulting UUID might be different from
     * the original ULID. The two values will not be binary-equal!
     *
     * Version 7 UUIDs and ULIDs are both derived from the Unix Epoch and randomly generated bytes. However, version 7
     * UUIDs also add the UUID version and variant bits. When converting a ULID to a version 7 UUID, if the ULID does
     * not already have these bits set, we set them, which might result in the UUID no longer having binary equality
     * with the ULID.
     */
    public function toUuidV7(): UuidV7
    {
        return new UuidV7((new Binary())->applyVersionAndVariant($this->toBytes(), Version::UnixTime));
    }

    /**
     * @param non-empty-string | null $ulid
     *
     * @return non-empty-string
     */
    private function getFormat(?Format $formatToReturn, ?string $ulid = null): string
    {
        $formatOfUlid = null;
        if ($ulid !== null) {
            $formatOfUlid = Format::tryFrom(strlen($ulid));
        }

        $formatOfUlid ??= $this->format;
        assert($formatOfUlid !== null);

        $ulid ??= $this->ulid;

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
