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

namespace Ramsey\Identifier\Service\BytesGenerator;

/**
 * Everything MonotonicBytesGenerator remembers from one call to the next
 *
 * @internal Not intended for use outside ramsey/identifier; may change without
 *     notice.
 */
final class MonotonicState
{
    /**
     * The sequence for calls without a date-time; null until the first one
     */
    public ?MonotonicSequence $clockSequence = null;

    /**
     * The sequence for calls with a date-time; null until the first one
     */
    public ?MonotonicSequence $datedSequence = null;

    /**
     * The index in $increments of the next random number; 0 when none is left
     */
    public int $incrementIndex = 0;

    /**
     * The identifier of the process that drew the random numbers; false if it
     * cannot be read
     */
    public int | false $incrementProcessId = false;

    /**
     * Random numbers the counters increase by, indexed from 1
     *
     * @var int[]
     */
    public array $increments = [];
}
