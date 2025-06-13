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

namespace Ramsey\Identifier\Service\Clock;

use DateTimeInterface;
use Ramsey\Identifier\Exception\InvalidArgument;

/**
 * A clock sequence that always returns the same pre-determined value. Calling `next()` does not advance the sequence.
 */
final readonly class FrozenClockSequence implements ClockSequence
{
    /**
     * @param int<0, max> $value A pre-determined sequence value.
     */
    public function __construct(private int $value)
    {
        if ($this->value < 0) {
            throw new InvalidArgument('The frozen clock sequence value must be a positive integer');
        }
    }

    public function current(?string $state = null, ?DateTimeInterface $dateTime = null): int
    {
        return $this->value;
    }

    /**
     * **WARNING**: The clock sequence does not advance for {@see FrozenClockSequence}s.
     *
     * {@inheritDoc}
     */
    public function next(?string $state = null, ?DateTimeInterface $dateTime = null): int
    {
        return $this->value;
    }
}
