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
use Identifier\Uuid\Variant;
use Ramsey\Identifier\Exception\InvalidArgumentException;
use Ramsey\Identifier\Exception\NotComparableException;
use Stringable;

use function assert;
use function bin2hex;
use function gettype;
use function hex2bin;
use function is_scalar;
use function is_string;
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
trait StandardUuid
{
    use Validation;

    private readonly Format $format;

    /**
     * Constructs an {@see \Identifier\UuidInterface} instance
     *
     * @param string $uuid A representation of the UUID in either string
     *     standard, hexadecimal, or bytes form
     */
    public function __construct(private readonly string $uuid)
    {
        $format = Format::tryFrom(strlen($this->uuid));

        if (!$this->isValid($this->uuid, $format)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid version %d UUID: "%s"',
                $this->getVersion()->value,
                $this->uuid,
            ));
        }

        assert($format !== null);

        $this->format = $format;
    }

    /**
     * @inheritDoc
     */
    public function __serialize(): array
    {
        return ['uuid' => $this->uuid];
    }

    public function __toString(): string
    {
        return $this->getFormat(Format::String);
    }

    /**
     * @inheritDoc
     */
    public function __unserialize(array $data): void
    {
        assert(isset($data['uuid']), "'uuid' is not set in serialized data");
        assert(is_string($data['uuid']), "'uuid' in serialized data is not a string");

        /** @psalm-suppress UnusedMethodCall */
        $this->__construct($data['uuid']);
    }

    /**
     * @psalm-return -1 | 0 | 1
     */
    public function compareTo(mixed $other): int
    {
        if ($other === null || is_scalar($other) || $other instanceof Stringable) {
            $other = (string) $other;
            if ($this->isValid($other, Format::tryFrom(strlen($other)))) {
                $other = $this->getFormat(Format::String, $other);
            }

            $compare = strcasecmp($this->getFormat(Format::String), $other);

            return match (true) {
                $compare < 0 => - 1,
                $compare > 0 => 1,
                default => 0,
            };
        }

        throw new NotComparableException(sprintf(
            'Comparison with values of type "%s" is not supported',
            gettype($other),
        ));
    }

    public function equals(mixed $other): bool
    {
        try {
            return $this->compareTo($other) === 0;
        } catch (NotComparableException) {
            return false;
        }
    }

    public function getVariant(): Variant
    {
        return Variant::Rfc4122;
    }

    public function jsonSerialize(): string
    {
        return $this->getFormat(Format::String);
    }

    public function toString(): string
    {
        return $this->getFormat(Format::String);
    }

    public function toBytes(): string
    {
        return $this->getFormat(Format::Bytes);
    }

    /**
     * @return non-empty-string
     */
    public function toHexadecimal(): string
    {
        return $this->getFormat(Format::Hexadecimal);
    }

    /**
     * @return int | numeric-string
     */
    public function toInteger(): int | string
    {
        /** @psalm-var numeric-string */
        return BigInteger::fromBase($this->getFormat(Format::Hexadecimal), 16)->__toString();
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
    private function getFormat(Format $formatToReturn, ?string $uuid = null): string
    {
        $formatOfUuid = $uuid ? Format::tryFrom(strlen($uuid)) : $this->format;
        $uuid ??= $this->uuid;

        /** @var non-empty-string */
        return match ($formatToReturn) {
            Format::String => match ($formatOfUuid) {
                Format::String => strtolower($uuid),
                Format::Hexadecimal => $this->toStringFromHex(strtolower($uuid)),
                Format::Bytes => $this->toStringFromHex(bin2hex($uuid)),
                default => $uuid,
            },
            Format::Hexadecimal => match ($formatOfUuid) {
                Format::String => strtolower(str_replace('-', '', $uuid)),
                Format::Hexadecimal => strtolower($uuid),
                Format::Bytes => bin2hex($uuid),
                default => $uuid,
            },
            default => match ($formatOfUuid) {
                Format::String => hex2bin(str_replace('-', '', $uuid)),
                Format::Hexadecimal => hex2bin($uuid),
                default => $uuid,
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
