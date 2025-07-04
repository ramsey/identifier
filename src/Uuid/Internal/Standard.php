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

use Brick\Math\BigInteger;
use Identifier\BytesIdentifier;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotComparable;
use Ramsey\Identifier\Uuid\MicrosoftGuid;
use Ramsey\Identifier\Uuid\Variant;
use Stringable;

use function assert;
use function bin2hex;
use function gettype;
use function hex2bin;
use function is_scalar;
use function sprintf;
use function str_replace;
use function strcasecmp;
use function strlen;
use function strtolower;
use function substr;

use const PHP_INT_MAX;

/**
 * Provides common methods for UUIDs.
 *
 * @internal Not intended for use outside ramsey/identifier; may change without notice.
 */
trait Standard
{
    use Validation;

    private readonly ?Format $format;

    /**
     * @param non-empty-string $uuid A representation of the UUID as a string with dashes, hexadecimal, or byte string.
     *
     * @throws InvalidArgument
     */
    public function __construct(private readonly string $uuid)
    {
        $this->format = Format::tryFrom(strlen($this->uuid));

        if (!$this->isValid($this->uuid, $this->format)) {
            throw new InvalidArgument(sprintf(
                'Invalid version %d UUID: "%s"',
                $this->getVersion()->value,
                $this->uuid,
            ));
        }
    }

    /**
     * @return array{uuid: non-empty-string}
     */
    public function __serialize(): array
    {
        return ['uuid' => $this->uuid];
    }

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return $this->getFormat(Format::String);
    }

    /**
     * @param array{uuid: non-empty-string} $data
     *
     * @throws InvalidArgument
     */
    public function __unserialize(array $data): void
    {
        assert(isset($data['uuid']), "'uuid' is not set in serialized data");

        $this->__construct($data['uuid']);
    }

    /**
     * @throws NotComparable
     */
    public function compareTo(mixed $other): int
    {
        // Microsoft GUID bytes are in a different order, even though the string representations might be identical, so
        // we'll skip MicrosoftGuid bytes comparisons.
        if ($other instanceof BytesIdentifier && !$other instanceof MicrosoftGuid) {
            return $this->toBytes() <=> $other->toBytes();
        }

        if ($other === null || is_scalar($other) || $other instanceof Stringable) {
            $other = (string) $other;
            if ($this->isValid($other, Format::tryFrom(strlen($other)))) {
                $other = $this->getFormat(Format::String, $other);
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

    public function getVariant(): Variant
    {
        return Variant::Rfc;
    }

    /**
     * @return non-empty-string
     */
    public function jsonSerialize(): string
    {
        return $this->getFormat(Format::String);
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
        /** @var numeric-string $uuidInteger */
        $uuidInteger = $this->getFormat(null);

        if ($uuidInteger <= PHP_INT_MAX) {
            /** @var int<0, max> */
            return (int) $uuidInteger;
        }

        return $uuidInteger;
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->getFormat(Format::String);
    }

    /**
     * @return non-empty-string
     */
    public function toUrn(): string
    {
        return 'urn:uuid:' . $this->getFormat(Format::String);
    }

    /**
     * @return non-empty-string
     */
    private function getFormat(?Format $formatToReturn, ?string $uuid = null): string
    {
        $formatOfUuid = null;
        if ($uuid !== null) {
            $formatOfUuid = Format::tryFrom(strlen($uuid));
        }

        $formatOfUuid ??= $this->format;
        assert($formatOfUuid !== null);

        $uuid ??= $this->uuid;

        /** @var non-empty-string */
        return match ($formatToReturn) {
            Format::Bytes => match ($formatOfUuid) {
                Format::Bytes => $uuid,
                Format::Hex => hex2bin($uuid),
                Format::String => hex2bin(str_replace('-', '', $uuid)),
            },
            Format::Hex => match ($formatOfUuid) {
                Format::Bytes => bin2hex($uuid),
                Format::Hex => strtolower($uuid),
                Format::String => strtolower(str_replace('-', '', $uuid)),
            },
            Format::String => match ($formatOfUuid) {
                Format::Bytes => $this->toStringFromHex(bin2hex($uuid)),
                Format::Hex => $this->toStringFromHex(strtolower($uuid)),
                Format::String => strtolower($uuid),
            },
            default => match ($formatOfUuid) {
                Format::Bytes => BigInteger::fromBytes($uuid, false)->toBase(10),
                Format::Hex => BigInteger::fromBase($uuid, 16)->toBase(10),
                Format::String => BigInteger::fromBase(str_replace('-', '', $uuid), 16)->toBase(10),
            },
        };
    }

    /**
     * @return non-empty-string
     */
    private function toStringFromHex(string $hex): string
    {
        return sprintf(
            '%08s-%04s-%04s-%04s-%012s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20),
        );
    }
}
