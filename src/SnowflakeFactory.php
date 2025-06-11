<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is open source software: you can distribute it and/or modify it under the terms of the MIT License
 * (the "License"). You may not use this file except in compliance with the License.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Ramsey\Identifier;

use DateTimeInterface;
use Identifier\BytesIdentifierFactory;
use Identifier\DateTimeIdentifierFactory;
use Identifier\IntegerIdentifierFactory;
use Identifier\StringIdentifierFactory;
use Ramsey\Identifier\Exception\InvalidArgument;

/**
 * A factory for creating Snowflake IDs.
 */
interface SnowflakeFactory extends
    BytesIdentifierFactory,
    DateTimeIdentifierFactory,
    IntegerIdentifierFactory,
    StringIdentifierFactory
{
    public function create(): Snowflake;

    public function createFromBytes(string $identifier): Snowflake;

    public function createFromDateTime(DateTimeInterface $dateTime): Snowflake;

    /**
     * Creates a new instance of a Snowflake ID from the given hexadecimal representation.
     *
     * @throws InvalidArgument MUST throw if the identifier is not a legal value.
     */
    public function createFromHexadecimal(string $identifier): Snowflake;

    public function createFromInteger(int | string $identifier): Snowflake;

    public function createFromString(string $identifier): Snowflake;
}
