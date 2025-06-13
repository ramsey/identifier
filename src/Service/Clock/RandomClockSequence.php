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
use Ramsey\Identifier\Service\Sequence\RandomSequence;

/**
 * A clock sequence that is randomly generated and does not use stable storage.
 */
final class RandomClockSequence implements ClockSequence
{
    private RandomSequence $sequence;

    public function __construct()
    {
        $this->sequence = new RandomSequence(min: 0);
    }

    public function current(?string $state = null, ?DateTimeInterface $dateTime = null): int
    {
        /** @var int<0, max> */
        return $this->sequence->current();
    }

    /**
     * **WARNING**: The next value in the sequence for {@see RandomClockSequence} is randomly generated. It is not
     * guaranteed to be a value greater than or less than the current value.
     *
     * {@inheritDoc}
     */
    public function next(?string $state = null, ?DateTimeInterface $dateTime = null): int
    {
        // Prevent against randomly generating the same as the current value.
        $current = $this->current();

        do {
            /** @var int<0, max> $next */
            $next = $this->sequence->next();
        } while ($next === $current);

        return $next;
    }
}
