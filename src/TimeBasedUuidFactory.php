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

namespace Ramsey\Identifier;

use DateTimeInterface;
use Identifier\DateTimeIdentifierFactory;

/**
 * A factory for creating time-based UUIDs.
 */
interface TimeBasedUuidFactory extends DateTimeIdentifierFactory, UuidFactory
{
    public function create(): TimeBasedUuid;

    public function createFromBytes(string $identifier): TimeBasedUuid;

    public function createFromDateTime(DateTimeInterface $dateTime): TimeBasedUuid;

    public function createFromHexadecimal(string $identifier): TimeBasedUuid;

    public function createFromInteger(int | string $identifier): TimeBasedUuid;

    public function createFromString(string $identifier): TimeBasedUuid;
}
