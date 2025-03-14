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

namespace Ramsey\Identifier\Ulid\Utility;

use Brick\Math\BigInteger;
use Brick\Math\Exception\IntegerOverflowException;
use DateTimeImmutable;
use Identifier\BytesIdentifier;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotComparable;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Utility\Binary;
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

use const STR_PAD_LEFT;

/**
 * This internal trait provides functionality common to all types of ULIDs
 *
 * @internal
 */
trait Standard
{
    use Validation;

    private const CROCKFORD32_ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    private readonly string $ulid;
    private readonly ?Format $format;

    /**
     * Constructs a {@see \Ramsey\Identifier\Ulid} instance
     *
     * @param string $ulid A representation of the ULID in either Crockford
     *     base 32 or bytes form
     *
     * @throws InvalidArgument
     */
    public function __construct(string $ulid)
    {
        $original = $ulid;
        $this->format = Format::tryFrom(strlen($ulid));

        if ($this->format === Format::Ulid) {
            $ulid = strtr($ulid, 'IiLlOo', '111100');
        }

        $this->ulid = $ulid;

        if (!$this->isValid($this->ulid, $this->format)) {
            throw new InvalidArgument(sprintf('Invalid ULID: "%s"', $original));
        }
    }

    /**
     * @return array{ulid: string}
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
     * @param array{ulid: string} $data
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
            if ($this->isValid($other, Format::tryFrom(strlen($other)))) {
                $other = $this->getFormat(Format::Ulid, strtr($other, 'IiLlOo', '111100'));
            }

            return strcasecmp($this->toString(), $other);
        }

        throw new NotComparable(sprintf(
            'Comparison with values of type "%s" is not supported',
            gettype($other),
        ));
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
     * @return int | numeric-string
     */
    public function toInteger(): int | string
    {
        $bigInteger = BigInteger::fromArbitraryBase(
            $this->getFormat(Format::Ulid),
            self::CROCKFORD32_ALPHABET,
        );

        try {
            return $bigInteger->toInt();
        } catch (IntegerOverflowException) {
            /** @var numeric-string */
            return (string) $bigInteger;
        }
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
     * Both ULIDs and UUIDs are 128-bit integers. At the binary level, their
     * bits are identical.
     *
     * This method returns a UUID instance that has the same bytes as the ULID
     * from which it was created. As a result, the UUID might not be a valid
     * RFC 9562 variant UUID. If this is the case, the resulting UUID will be of
     * the type {@see \Ramsey\Identifier\Uuid\NonstandardUuid NonstandardUuid}.
     */
    public function toUuid(): Uuid
    {
        return (new UuidFactory())->createFromBytes($this->toBytes())->toTypedUuid();
    }

    /**
     * Returns this ULID as a version 7 UUID instance
     *
     * This method differs from {@see self::toUuid()} in that the bytes of the
     * resulting UUID might be different from the original ULID. The two values
     * will not be binary-equal!
     *
     * Version 7 UUIDs and ULIDs are both derived from the Unix Epoch and
     * randomly-generated bytes. However, version 7 UUIDs also add the UUID
     * version and variant bits. When converting a ULID to a version 7 UUID,
     * if the ULID does not already have these bits set, we set them, which
     * might result in the UUID no longer having binary equality with the ULID.
     */
    public function toUuidV7(): UuidV7
    {
        return new UuidV7((new Binary())->applyVersionAndVariant($this->toBytes(), Version::UnixTime));
    }

    /**
     * @return non-empty-string
     */
    private function getFormat(?Format $formatToReturn, ?string $ulid = null): string
    {
        $formatOfUlid = null;
        if ($ulid !== null) {
            $formatOfUlid = Format::tryFrom(strlen($ulid));
        }

        $formatOfUlid ??= $this->format;
        $ulid ??= $this->ulid;

        if ($formatOfUlid === null) {
            throw new InvalidArgument('Invalid ULID format');
        }

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
