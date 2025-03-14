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
use Identifier\Exception\OutOfRange;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\NotComparable;
use Ramsey\Identifier\Snowflake;

use function assert;

/**
 * @internal
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
