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

namespace Ramsey\Identifier\Snowflake\Internal;

use DateTimeImmutable;
use Override;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotComparable;
use Ramsey\Identifier\Exception\NotConvertible;
use Ramsey\Identifier\Snowflake;

use function is_int;
use function is_string;

/**
 * Provides common methods for Snowflake identifiers
 *
 * @internal Not intended for use outside ramsey/identifier; may change without
 *     notice.
 */
trait Standard
{
    private readonly Snowflake $snowflake;

    /**
     * @return array{snowflake: non-empty-string}
     */
    public function __serialize(): array
    {
        return ['snowflake' => $this->snowflake->toString()];
    }

    /**
     * @return non-empty-string
     */
    #[Override]
    public function __toString(): string
    {
        return $this->snowflake->toString();
    }

    /**
     * @param array{snowflake?: int<0, max> | numeric-string} $data
     *
     * @throws InvalidArgument if the data has no integer or string for the key
     *     "snowflake", or the value is not a valid identifier
     */
    public function __unserialize(array $data): void
    {
        $snowflake = $data['snowflake'] ?? null;

        if (!is_int($snowflake) && !is_string($snowflake)) {
            throw new InvalidArgument('The serialized data must have an integer or string value for "snowflake"');
        }

        $this->__construct($snowflake);
    }

    /**
     * @throws NotComparable
     */
    #[Override]
    public function compareTo(mixed $other): int
    {
        return $this->snowflake->compareTo($other);
    }

    #[Override]
    public function equals(mixed $other): bool
    {
        return $this->snowflake->equals($other);
    }

    /**
     * @throws NotConvertible when the date-time cannot be represented
     */
    #[Override]
    public function getDateTime(): DateTimeImmutable
    {
        return $this->snowflake->getDateTime();
    }

    /**
     * Returns a string of bytes that places the identifier in order among other
     * identifiers
     *
     * @return non-empty-string
     */
    #[Override]
    public function getSortKey(): string
    {
        return $this->snowflake->getSortKey();
    }

    /**
     * @return non-empty-string
     */
    #[Override]
    public function jsonSerialize(): string
    {
        return $this->snowflake->toString();
    }

    /**
     * @return non-empty-string
     */
    #[Override]
    public function toBytes(): string
    {
        /** @var non-empty-string */
        return $this->snowflake->toBytes();
    }

    /**
     * @return non-empty-string
     */
    #[Override]
    public function toHexadecimal(): string
    {
        /** @var non-empty-string */
        return $this->snowflake->toHexadecimal();
    }

    /**
     * @return int<0, max> | numeric-string
     */
    #[Override]
    public function toInteger(): int | string
    {
        /** @var int<0, max> | numeric-string */
        return $this->snowflake->toInteger();
    }

    /**
     * @return non-empty-string
     */
    #[Override]
    public function toString(): string
    {
        return $this->snowflake->toString();
    }
}
