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

use DateTimeInterface;
use Ramsey\Identifier\Exception\IdentifierException;
use Ramsey\Identifier\Exception\InvalidArgument;

/**
 * Generates bytes used to create identifiers
 */
interface BytesGenerator
{
    /**
     * Generates an n-length string of bytes
     *
     * @param positive-int $length The number of bytes to generate
     * @param DateTimeInterface | null $dateTime An optional date-time instance
     *     to use when generating the bytes; not all generators will need or use
     *     this parameter
     *
     * @return non-empty-string
     *
     * @throws InvalidArgument if the length is less than 1
     * @throws IdentifierException if the generator cannot generate the bytes
     */
    public function bytes(int $length = 16, ?DateTimeInterface $dateTime = null): string;
}
