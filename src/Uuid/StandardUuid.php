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
use Identifier\Uuid\UuidInterface;
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

    /**
     * Constructs an {@see \Identifier\UuidInterface} instance
     *
     * @param string $uuid A representation of the UUID in either string
     *     standard, hexadecimal, or bytes form
     */
    public function __construct(private readonly string $uuid)
    {
        if (!$this->isValid($this->uuid)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid version %d UUID: "%s"',
                $this->getVersion()->value,
                $this->uuid,
            ));
        }
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
        return $this->getFormat(Format::String, $this->uuid);
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
            if (!$other instanceof UuidInterface && $this->isValid((string) $other)) {
                $other = $this->getFormat(Format::String, (string) $other);
            }

            $compare = strcasecmp($this->getFormat(Format::String, $this->uuid), (string) $other);

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
        return $this->getFormat(Format::String, $this->uuid);
    }

    public function toString(): string
    {
        return $this->getFormat(Format::String, $this->uuid);
    }

    public function toBytes(): string
    {
        return $this->getFormat(Format::Bytes, $this->uuid);
    }

    /**
     * @return non-empty-string
     */
    public function toHexadecimal(): string
    {
        return $this->getFormat(Format::Hexadecimal, $this->uuid);
    }

    /**
     * @return int | numeric-string
     */
    public function toInteger(): int | string
    {
        /** @psalm-var numeric-string */
        return BigInteger::fromBase($this->getFormat(Format::Hexadecimal, $this->uuid), 16)->__toString();
    }

    /**
     * @return non-empty-string
     */
    public function toUrn(): string
    {
        return 'urn:uuid:' . $this->getFormat(Format::String, $this->uuid);
    }

    /**
     * @return non-empty-string
     */
    private function getFormat(Format $format, string $uuid): string
    {
        /** @var non-empty-string */
        return match ($format) {
            Format::String => match (strlen($uuid)) {
                36 => strtolower($uuid),
                32 => $this->toStringFromHex(strtolower($uuid)),
                16 => $this->toStringFromHex(bin2hex($uuid)),
                default => $uuid,
            },
            Format::Hexadecimal => match (strlen($uuid)) {
                36 => strtolower(str_replace('-', '', $uuid)),
                32 => strtolower($uuid),
                16 => bin2hex($uuid),
                default => $uuid,
            },
            default => match (strlen($uuid)) {
                36 => hex2bin(str_replace('-', '', $uuid)),
                32 => hex2bin($uuid),
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
