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

namespace Ramsey\Identifier\Service\Clock;

use DateTimeImmutable;
use StellaMaris\Clock\ClockInterface as Clock;

/**
 * A clock that always returns a pre-defined date-time
 */
final class FrozenClock implements Clock
{
    /**
     * @param DateTimeImmutable $dateTime The date-time instance this service
     *     should always return
     */
    public function __construct(private readonly DateTimeImmutable $dateTime)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->dateTime;
    }
}
