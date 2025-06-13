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

namespace Ramsey\Identifier\Service\BytesGenerator;

use DateTimeInterface;
use Ramsey\Identifier\Exception\InvalidArgument;

use function intdiv;
use function str_repeat;
use function strlen;
use function substr;

/**
 * A generator that returns a pre-determined byte string.
 */
final readonly class FixedBytesGenerator implements BytesGenerator
{
    private int $bytesLength;

    /**
     * @param non-empty-string $bytes
     */
    public function __construct(private string $bytes)
    {
        $this->bytesLength = strlen($this->bytes);

        if ($this->bytesLength === 0) {
            throw new InvalidArgument('The bytes must be a non-empty octet string');
        }
    }

    public function bytes(int $length = 16, ?DateTimeInterface $dateTime = null): string
    {
        $bytes = str_repeat($this->bytes, intdiv($length, $this->bytesLength) + 1);

        /** @var non-empty-string */
        return substr($bytes, 0, $length);
    }
}
