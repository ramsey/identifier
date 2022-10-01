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
use Ramsey\Identifier\Exception\InvalidArgumentException;
use Ramsey\Identifier\Exception\NotComparableException;
use Ramsey\Identifier\Uuid\Utility\Format;
use Stringable;

use function assert;
use function gettype;
use function hex2bin;
use function hexdec;
use function is_scalar;
use function is_string;
use function number_format;
use function sprintf;
use function str_pad;
use function strcasecmp;
use function strlen;
use function strtolower;
use function strtoupper;
use function substr;

use const STR_PAD_LEFT;

/**
 * This internal trait provides functionality common to all types of ULIDs
 *
 * @internal
 *
 * @psalm-immutable
 */
trait StandardUlid
{
    use Validation;

    private readonly int $format;

    /**
     * Constructs a {@see UlidInterface} instance
     *
     * @param string $ulid A representation of the ULID in either Crockford
     *     base 32 or bytes form
     *
     * @throws InvalidArgumentException
     */
    public function __construct(private readonly string $ulid)
    {
        $this->format = strlen($this->ulid);

        if (!$this->isValid($this->ulid, $this->format)) {
            throw new InvalidArgumentException(sprintf('Invalid ULID: "%s"', $this->ulid));
        }
    }

    /**
     * @inheritDoc
     */
    public function __serialize(): array
    {
        return ['ulid' => $this->ulid];
    }

    /**
     * @return non-empty-string
     */
    public function __toString()
    {
        return $this->getFormat(Format::FORMAT_ULID);
    }

    /**
     * @inheritDoc
     */
    public function __unserialize(array $data): void
    {
        assert(isset($data['ulid']), "'ulid' is not set in serialized data");
        assert(is_string($data['ulid']), "'ulid' in serialized data is not a string");

        /** @psalm-suppress UnusedMethodCall */
        $this->__construct($data['ulid']);
    }

    /**
     * @throws NotComparableException
     *
     * @psalm-return -1 | 0 | 1
     */
    public function compareTo(mixed $other): int
    {
        if ($other === null || is_scalar($other) || $other instanceof Stringable) {
            $other = (string) $other;
            if ($this->isValid($other, strlen($other))) {
                $other = $this->getFormat(Format::FORMAT_ULID, $other);
            }

            $compare = strcasecmp($this->getFormat(Format::FORMAT_ULID), $other);

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

    public function getDateTime(): DateTimeImmutable
    {
        $hex = $this->getFormat(Format::FORMAT_HEX);
        $timestamp = sprintf('%012s', substr($hex, 0, 12));
        $unixTimestamp = number_format(hexdec($timestamp) / 1000, 6, '.', '');

        return new DateTimeImmutable('@' . $unixTimestamp);
    }

    /**
     * @return non-empty-string
     */
    public function jsonSerialize(): string
    {
        return $this->getFormat(Format::FORMAT_ULID);
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
        $bigInteger = BigInteger::fromArbitraryBase(
            $this->getFormat(Format::FORMAT_ULID),
            Format::CROCKFORD32_ALPHABET,
        );

        try {
            return $bigInteger->toInt();
        } catch (IntegerOverflowException) {
            /** @var numeric-string */
            return $bigInteger->__toString();
        }
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->getFormat(Format::FORMAT_ULID);
    }

    /**
     * @return non-empty-string
     *
     * @psalm-param 32 | 26 | 16 $formatToReturn
     * @psalm-suppress ImpureMethodCall
     */
    private function getFormat(int $formatToReturn, ?string $ulid = null): string
    {
        /** @psalm-var 32 | 26 | 16 $formatOfUlid */
        $formatOfUlid = $ulid ? strlen($ulid) : $this->format;
        $ulid ??= $this->ulid;

        /** @var non-empty-string */
        return match ($formatToReturn) {
            Format::FORMAT_ULID => match ($formatOfUlid) {
                Format::FORMAT_ULID => strtoupper($ulid),
                Format::FORMAT_HEX => sprintf(
                    '%026s',
                    BigInteger::fromBase($ulid, 16)->toArbitraryBase(Format::CROCKFORD32_ALPHABET),
                ),
                Format::FORMAT_BYTES => sprintf(
                    '%026s',
                    BigInteger::fromBytes($ulid, false)->toArbitraryBase(Format::CROCKFORD32_ALPHABET),
                ),
            },
            Format::FORMAT_HEX => match ($formatOfUlid) {
                Format::FORMAT_ULID => sprintf(
                    '%032s',
                    BigInteger::fromArbitraryBase($ulid, Format::CROCKFORD32_ALPHABET)->toBase(16),
                ),
                Format::FORMAT_HEX => strtolower($ulid),
                Format::FORMAT_BYTES => sprintf(
                    '%032s',
                    BigInteger::fromBytes($ulid, false)->toBase(16),
                ),
            },
            Format::FORMAT_BYTES => match ($formatOfUlid) {
                Format::FORMAT_ULID => str_pad(
                    BigInteger::fromArbitraryBase($ulid, Format::CROCKFORD32_ALPHABET)->toBytes(false),
                    16,
                    "\x00",
                    STR_PAD_LEFT,
                ),
                Format::FORMAT_HEX => str_pad((string) hex2bin($ulid), 16, "\x00", STR_PAD_LEFT),
                Format::FORMAT_BYTES => str_pad($ulid, 16, "\x00", STR_PAD_LEFT),
            },
        };
    }
}
