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

use Brick\Math\BigInteger;
use Identifier\BinaryIdentifier;
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

/**
 * This internal trait provides functionality common to all types of UUIDs
 *
 * @internal
 *
 * @psalm-immutable
 */
trait Standard
{
    use Validation;

    private readonly int $format;

    /**
     * Constructs a {@see \Ramsey\Identifier\Uuid} instance
     *
     * @param string $uuid A representation of the UUID in either string
     *     standard, hexadecimal, or bytes form
     *
     * @throws InvalidArgument
     */
    public function __construct(private readonly string $uuid)
    {
        $this->format = strlen($this->uuid);

        if (!$this->isValid($this->uuid, $this->format)) {
            throw new InvalidArgument(sprintf(
                'Invalid version %d UUID: "%s"',
                $this->getVersion()->value,
                $this->uuid,
            ));
        }
    }

    /**
     * @return array{uuid: string}
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
        return $this->getFormat(Format::FORMAT_STRING);
    }

    /**
     * @param array{uuid: string} $data
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
        // Microsoft GUID bytes are in a different order, even though the string
        // representations might be identical, so we'll skip MicrosoftGuid bytes
        // comparisons.
        if ($other instanceof BinaryIdentifier && !$other instanceof MicrosoftGuid) {
            /** @psalm-suppress ImpureMethodCall BinaryIdentifier doesn't make any purity guarantees. */
            return $this->toBytes() <=> $other->toBytes();
        }

        if ($other === null || is_scalar($other) || $other instanceof Stringable) {
            $other = (string) $other;
            if ($this->isValid($other, strlen($other))) {
                $other = $this->getFormat(Format::FORMAT_STRING, $other);
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

    public function getVariant(): Variant
    {
        return Variant::Rfc4122;
    }

    /**
     * @return non-empty-string
     */
    public function jsonSerialize(): string
    {
        return $this->getFormat(Format::FORMAT_STRING);
    }

    /**
     * @return non-empty-string
     */
    public function toBytes(): string
    {
        return $this->getFormat(Format::FORMAT_BYTES);
    }

    /**
     * @return non-empty-string
     */
    public function toHexadecimal(): string
    {
        return $this->getFormat(Format::FORMAT_HEX);
    }

    /**
     * @return int | numeric-string
     */
    public function toInteger(): int | string
    {
        /** @psalm-var numeric-string */
        return BigInteger::fromBase($this->getFormat(Format::FORMAT_HEX), 16)->__toString();
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->getFormat(Format::FORMAT_STRING);
    }

    /**
     * @return non-empty-string
     */
    public function toUrn(): string
    {
        return 'urn:uuid:' . $this->getFormat(Format::FORMAT_STRING);
    }

    /**
     * @return non-empty-string
     *
     * @psalm-param 36 | 32 | 16 $formatToReturn
     */
    private function getFormat(int $formatToReturn, ?string $uuid = null): string
    {
        /** @psalm-var 36 | 32 | 16 $formatOfUuid */
        $formatOfUuid = $uuid ? strlen($uuid) : $this->format;
        $uuid ??= $this->uuid;

        /** @var non-empty-string */
        return match ($formatToReturn) {
            Format::FORMAT_STRING => match ($formatOfUuid) {
                Format::FORMAT_STRING => strtolower($uuid),
                Format::FORMAT_HEX => $this->toStringFromHex(strtolower($uuid)),
                Format::FORMAT_BYTES => $this->toStringFromHex(bin2hex($uuid)),
            },
            Format::FORMAT_HEX => match ($formatOfUuid) {
                Format::FORMAT_STRING => strtolower(str_replace('-', '', $uuid)),
                Format::FORMAT_HEX => strtolower($uuid),
                Format::FORMAT_BYTES => bin2hex($uuid),
            },
            Format::FORMAT_BYTES => match ($formatOfUuid) {
                Format::FORMAT_STRING => hex2bin(str_replace('-', '', $uuid)),
                Format::FORMAT_HEX => hex2bin($uuid),
                Format::FORMAT_BYTES => $uuid,
            },
        };
    }

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
