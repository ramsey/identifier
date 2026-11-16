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

namespace Ramsey\Identifier\Service\Clock;

use DateTimeImmutable;
use Psr\Clock\ClockInterface as Clock;

/**
 * A clock that always returns a pre-defined date-time.
 */
final readonly class FrozenClock implements Clock
{
    /**
     * @param DateTimeImmutable $dateTime The date-time instance this service should always return.
     */
    public function __construct(private DateTimeImmutable $dateTime)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->dateTime;
    }
}
