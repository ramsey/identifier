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

namespace Ramsey\Identifier\Uuid\Internal;

use Brick\Math\BigInteger;
use Identifier\Identifier;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Internal\ComparesBySortKey;
use Ramsey\Identifier\Internal\IntegerString;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Variant;

use function bin2hex;
use function hex2bin;
use function is_string;
use function preg_match;
use function sprintf;
use function str_replace;
use function strlen;
use function strtolower;
use function substr;

/**
 * Provides common methods for UUIDs
 *
 * @internal Not intended for use outside ramsey/identifier; may change without
 *     notice.
 */
trait Standard
{
    use ComparesBySortKey;
    use Validation;

    private readonly Format $format;

    /**
     * @param non-empty-string $uuid A representation of the UUID as a string
     *     with dashes, hexadecimal, or byte string
     *
     * @throws InvalidArgument
     */
    public function __construct(private readonly string $uuid)
    {
        $format = Format::tryFrom(strlen($this->uuid));

        if ($format === null || !$this->isValid($this->uuid, $format)) {
            throw new InvalidArgument(sprintf(
                'Invalid version %d UUID: "%s"',
                $this->getVersion()->value,
                $this->uuid,
            ));
        }

        $this->format = $format;
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
     * @param array{uuid?: non-empty-string} $data
     *
     * @throws InvalidArgument if the data has no string for the key "uuid", or
     *     the string is not a valid identifier
     */
    public function __unserialize(array $data): void
    {
        if (!is_string($data['uuid'] ?? null)) {
            throw new InvalidArgument('The serialized data must have a string value for "uuid"');
        }

        $this->__construct($data['uuid']);
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

        if (IntegerString::fitsNativeInteger($uuidInteger)) {
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
    private function getFormat(?Format $formatToReturn): string
    {
        $formatOfUuid = $this->format;
        $uuid = $this->uuid;

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
                Format::String => BigInteger::fromBase(str_replace('-', '', $uuid), 16)->toBase(10), // @phpstan-ignore argument.type
            },
        };
    }

    private function isSameKind(Identifier $other): bool
    {
        return $other instanceof Uuid;
    }

    /**
     * @return non-empty-string | null
     */
    private function sortKeyFromString(string $value): ?string
    {
        if (preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/i', $value) !== 1) {
            return null;
        }

        /** @var non-empty-string */
        return (string) hex2bin(str_replace('-', '', $value));
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
