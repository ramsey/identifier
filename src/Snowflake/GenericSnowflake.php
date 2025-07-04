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

namespace Ramsey\Identifier\Snowflake;

use DateTimeImmutable;
use Identifier\BytesIdentifier;
use Identifier\Exception\OutOfRange;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotComparable;
use Ramsey\Identifier\Snowflake;
use Ramsey\Identifier\Snowflake\Internal\Format;
use Ramsey\Identifier\Snowflake\Internal\Time;
use Ramsey\Identifier\Snowflake\Internal\Validation;
use Stringable;

use function assert;
use function gettype;
use function is_scalar;
use function sprintf;

/**
 * A generic Snowflake identifier that may use any epoch offset.
 *
 * @link https://en.wikipedia.org/wiki/Snowflake_ID Snowflake ID.
 */
final readonly class GenericSnowflake implements Snowflake
{
    use Validation;

    private const TIMESTAMP_BIT_SHIFTS = 22;

    private Time $time;

    private int $epochOffset;

    /**
     * @param int<0, max> | numeric-string $snowflake A representation of the Snowflake in integer or numeric string form.
     * @param Epoch | int $epochOffset The Snowflake identifier's offset from the Unix Epoch in milliseconds.
     *
     * @throws InvalidArgument
     */
    public function __construct(
        private int | string $snowflake,
        Epoch | int $epochOffset,
    ) {
        if (!$this->isValid($this->snowflake)) {
            throw new InvalidArgument(sprintf('Invalid Snowflake: "%s"', $this->snowflake));
        }

        if ($epochOffset instanceof Epoch) {
            $epochOffset = $epochOffset->value;
        }

        $this->epochOffset = $epochOffset;
        $this->time = new Time();
    }

    /**
     * @return array{
     *     snowflake: int<0, max> | numeric-string,
     *     epochOffset: int,
     * }
     */
    public function __serialize(): array
    {
        return ['snowflake' => $this->snowflake, 'epochOffset' => $this->epochOffset];
    }

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return (string) $this->snowflake;
    }

    /**
     * @param array{
     *     snowflake: int<0, max> | numeric-string,
     *     epochOffset: int,
     * } $data
     *
     * @throws InvalidArgument
     */
    public function __unserialize(array $data): void
    {
        assert(isset($data['snowflake']), "'snowflake' is not set in serialized data");
        assert(isset($data['epochOffset']), "'epochOffset' is not set in serialized data");

        $this->__construct($data['snowflake'], $data['epochOffset']);
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
            return (string) $this->snowflake <=> (string) $other;
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

    /**
     * @throws OutOfRange
     */
    public function getDateTime(): DateTimeImmutable
    {
        return $this->time->getDateTimeForSnowflake($this, $this->epochOffset, self::TIMESTAMP_BIT_SHIFTS);
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
        return Format::formatBytes($this->snowflake);
    }

    /**
     * @return non-empty-string
     */
    public function toHexadecimal(): string
    {
        return Format::formatHex($this->snowflake);
    }

    /**
     * @return int<0, max> | numeric-string
     */
    public function toInteger(): int | string
    {
        return Format::formatInt($this->snowflake);
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return (string) $this->snowflake;
    }
}
