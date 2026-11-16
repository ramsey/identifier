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

namespace Ramsey\Identifier\Service\Sequence;

/**
 * A sequence that always returns the same pre-determined value. Calling `next()` does not advance the sequence.
 */
final readonly class FrozenSequence implements Sequence
{
    /**
     * @param int | string $value A pre-determined sequence value.
     */
    public function __construct(private int | string $value)
    {
    }

    public function current(?string $state = null): int | string
    {
        return $this->value;
    }

    /**
     * **WARNING**: The sequence does not advance for {@see FrozenSequence}s.
     *
     * {@inheritDoc}
     */
    public function next(?string $state = null): int | string
    {
        return $this->value;
    }
}
