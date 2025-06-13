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

namespace Ramsey\Identifier\Snowflake\Internal;

use DateTimeImmutable;
use Identifier\Exception\OutOfRange;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotComparable;
use Ramsey\Identifier\Snowflake;

use function assert;

/**
 * Provides common methods for Snowflake identifiers.
 *
 * @internal Not intended for use outside ramsey/identifier; may change without notice.
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
    public function __toString(): string
    {
        return $this->snowflake->toString();
    }

    /**
     * @param array{snowflake: int<0, max> | numeric-string} $data
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
        return $this->snowflake->compareTo($other);
    }

    public function equals(mixed $other): bool
    {
        return $this->snowflake->equals($other);
    }

    public function getDateTime(): DateTimeImmutable
    {
        return $this->snowflake->getDateTime();
    }

    /*
     * @return non-empty-string
     */
    public function jsonSerialize(): string
    {
        return $this->snowflake->toString();
    }

    /**
     * @return non-empty-string
     */
    public function toBytes(): string
    {
        /** @var non-empty-string */
        return $this->snowflake->toBytes();
    }

    /**
     * @return non-empty-string
     */
    public function toHexadecimal(): string
    {
        /** @var non-empty-string */
        return $this->snowflake->toHexadecimal();
    }

    /**
     * @return int<0, max> | numeric-string
     *
     * @throws OutOfRange
     */
    public function toInteger(): int | string
    {
        /** @var int<0, max> | numeric-string */
        return $this->snowflake->toInteger();
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->snowflake->toString();
    }
}
