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
use Ramsey\Identifier\Exception\InvalidArgument;

use function random_bytes;
use function sprintf;

/**
 * A generator that uses PHP's built-in `random_bytes()` function to generate
 * cryptographically secure random bytes
 *
 * @link https://www.php.net/random_bytes random_bytes()
 */
final class RandomBytesGenerator implements BytesGenerator
{
    /**
     * @throws InvalidArgument if the length is less than 1
     */
    public function bytes(int $length = 16, ?DateTimeInterface $dateTime = null): string
    {
        if ($length < 1) {
            throw new InvalidArgument(sprintf('The length must be a positive integer; received %d', $length));
        }

        return random_bytes($length);
    }
}
