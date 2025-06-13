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

namespace Ramsey\Identifier\Service\Sequence;

use function random_int;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * Uses PHP's `random_int()` function to generate a random sequence value.
 *
 * > [!CAUTION]
 * > Values generated using RandomSequence are not sequential or monotonic. They may be positive or negative integers.
 */
final class RandomSequence implements Sequence
{
    private int $current;

    /**
     * @param int $min The minimum value allowed in this random sequence (inclusive).
     * @param int $max The maximum value allowed in this random sequence (inclusive).
     */
    public function __construct(
        private readonly int $min = PHP_INT_MIN,
        private readonly int $max = PHP_INT_MAX,
    ) {
        // Initialize the random sequence.
        $this->current = random_int($this->min, $this->max);
    }

    public function current(?string $state = null): int
    {
        return $this->current;
    }

    public function next(?string $state = null): int
    {
        return $this->current = random_int($this->min, $this->max);
    }
}
