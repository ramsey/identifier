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
 * The timestamp and counter that MonotonicBytesGenerator last returned for one
 * kind of call, kept so the next ones can be greater
 *
 * @internal Not intended for use outside ramsey/identifier; may change without
 *     notice.
 */
final class MonotonicSequence
{
    /**
     * @param int $millisecond The count of milliseconds since the Unix Epoch
     * @param int $fraction The fraction of the millisecond, from 0 through 4095
     * @param int $counter The 62-bit counter
     * @param int | false $processId The identifier of the process that last
     *     started or stepped the sequence; false if it cannot be read
     * @param int | null $givenMillisecond For a date-time passed by a caller,
     *     its count of milliseconds
     * @param int | null $givenFraction For a date-time passed by a caller, its
     *     fraction of the millisecond
     */
    public function __construct(
        public int $millisecond,
        public int $fraction,
        public int $counter,
        public int | false $processId,
        public ?int $givenMillisecond = null,
        public ?int $givenFraction = null,
    ) {
    }
}
