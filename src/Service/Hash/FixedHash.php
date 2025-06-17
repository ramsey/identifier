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

namespace Ramsey\Identifier\Service\Hash;

use Ramsey\Identifier\Exception\InvalidArgument;

use function hex2bin;
use function sprintf;

/**
 * A hash that returns a pre-determined message digest.
 */
final readonly class FixedHash implements Hash
{
    /**
     * @var non-empty-string
     */
    private string $bytes;

    /**
     * @param non-empty-string $hash A hexadecimal string representing a message digest.
     */
    public function __construct(private string $hash)
    {
        /** @var non-empty-string | false $bytes */
        $bytes = @hex2bin($this->hash);

        if ($bytes === false || $bytes === '') {
            throw new InvalidArgument(sprintf(
                'The hash value "%s" must be a non-empty hexadecimal string with an even length',
                $this->hash,
            ));
        }

        $this->bytes = $bytes;
    }

    public function hash(string $data, bool $binary = false): string
    {
        return $binary ? $this->bytes : $this->hash;
    }
}
