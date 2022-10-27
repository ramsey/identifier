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

namespace Ramsey\Identifier\Snowflake\Utility;

use DateTimeImmutable;
use Identifier\BinaryIdentifier;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotComparable;
use Stringable;

use function assert;
use function gettype;
use function is_scalar;
use function sprintf;
use function strcmp;

/**
 * This internal trait provides functionality common to all types of Snowflakes
 *
 * @internal
 *
 * @psalm-immutable
 */
trait Standard
{
    use Validation;

    private readonly Format $format;
    private readonly Time $time;

    /**
     * Returns the count in milliseconds from which this Snowflake is offset
     * from the Unix Epoch
     *
     * If supporting 32-bit platforms, this may return a numeric string integer.
     *
     * @return int | numeric-string
     */
    abstract protected function getEpochOffset(): int | string;

    /**
     * Constructs a {@see \Ramsey\Identifier\Snowflake} instance
     *
     * @param int | numeric-string $snowflake A representation of the
     *     Snowflake in integer or numeric string form
     *
     * @throws InvalidArgument
     */
    public function __construct(private readonly int | string $snowflake)
    {
        if (!$this->isValid($this->snowflake)) {
            throw new InvalidArgument(sprintf('Invalid Snowflake: "%s"', $this->snowflake));
        }

        $this->format = new Format();
        $this->time = new Time();
    }

    /**
     * @return array{snowflake: int | numeric-string}
     */
    public function __serialize(): array
    {
        return ['snowflake' => $this->snowflake];
    }

    /**
     * @return non-empty-string
     */
    public function __toString()
    {
        return (string) $this->snowflake;
    }

    /**
     * @param array{snowflake: int | numeric-string} $data
     *
     * @throws InvalidArgument
     */
    public function __unserialize(array $data): void
    {
        assert(isset($data['snowflake']), "'snowflake' is not set in serialized data");

        $this->__construct($data['snowflake']);
    }

    /**
     * @throws NotComparable
     */
    public function compareTo(mixed $other): int
    {
        if ($other instanceof BinaryIdentifier) {
            /** @psalm-suppress ImpureMethodCall */
            return strcmp($this->toBytes(), $other->toBytes());
        }

        if ($other === null || is_scalar($other) || $other instanceof Stringable) {
            return strcmp((string) $this->snowflake, (string) $other);
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

    /**
     * @psalm-suppress ImpureMethodCall
     */
    public function getDateTime(): DateTimeImmutable
    {
        return $this->time->getDateTimeForSnowflake($this, $this->getEpochOffset());
    }

    /**
     * @return non-empty-string
     */
    public function jsonSerialize(): string
    {
        return (string) $this->snowflake;
    }

    /**
     * @return non-empty-string
     */
    public function toBytes(): string
    {
        /**
         * @psalm-suppress ImpureMethodCall
         * @var non-empty-string
         */
        return $this->format->format($this->snowflake, Format::FORMAT_BYTES);
    }

    /**
     * @return non-empty-string
     */
    public function toHexadecimal(): string
    {
        /**
         * @psalm-suppress ImpureMethodCall
         * @var non-empty-string
         */
        return $this->format->format($this->snowflake, Format::FORMAT_HEX);
    }

    /**
     * @return int | numeric-string
     */
    public function toInteger(): int | string
    {
        /**
         * @psalm-suppress ImpureMethodCall
         * @var int | numeric-string
         */
        return $this->format->format($this->snowflake, Format::FORMAT_INT);
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return (string) $this->snowflake;
    }
}
