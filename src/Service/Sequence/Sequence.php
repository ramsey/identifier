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

/**
 * Derives a sequence value.
 *
 * Sequences may be ascending or descending, depending on the nature of the sequence.
 */
interface Sequence
{
    /**
     * Returns the current sequence value for the given state.
     *
     * @param non-empty-string | null $state If provided, the state is treated as a namespace from which the sequence
     *     value will be returned; each unique state has a difference sequence.
     */
    public function current(?string $state = null): int | string;

    /**
     * Advances the sequence and returns the next value for the given state.
     *
     * If the sequence has reached the maximum value allowed for the given state, it may throw a {@see SequenceOverflow}
     * exception. Some sequences may choose to exhibit other behavior, such as rolling over the value.
     *
     * @param non-empty-string | null $state If provided, the state is treated as a namespace within which the next
     *     sequence value will be generated; each unique state has a different sequence.
     *
     * @throws SequenceOverflow when the sequence for a given state cannot be increased or decreased beyond its current
     *     value.
     */
    public function next(?string $state = null): int | string;
}
