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

namespace Ramsey\Identifier\Ulid;

use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Ulid as UlidInterface;
use Ramsey\Identifier\Ulid\Internal\Format;
use Ramsey\Identifier\Ulid\Internal\Standard;

use function sprintf;
use function strlen;

/**
 * The Max ULID is a special form of ULID that has all 128 bits set to one (`1`).
 *
 * > Technically, a 26-character Base32 encoded string can contain 130 bits of information, whereas a ULID must only
 * > contain 128 bits. Therefore, the largest valid ULID encoded in Base32 is `7ZZZZZZZZZZZZZZZZZZZZZZZZZ`, which
 * > corresponds to an epoch time of `281474976710655` or `2 ^ 48 - 1`.
 *
 * @link https://github.com/ulid/spec ULID specification.
 */
final readonly class MaxUlid implements UlidInterface
{
    use Standard;

    private const MAX = "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";

    /**
     * @param non-empty-string $ulid A representation of a ULID in either Crockford base-32 or byte form.
     *
     * @throws InvalidArgument
     */
    public function __construct(private string $ulid = self::MAX)
    {
        $this->format = Format::tryFrom(strlen($ulid));

        if (!$this->isValid($this->ulid, $this->format)) {
            throw new InvalidArgument(sprintf('Invalid Max ULID: "%s"', $this->ulid));
        }
    }

    /**
     * @phpstan-assert-if-true non-empty-string $ulid
     */
    private function isValid(string $ulid, ?Format $format): bool
    {
        return $this->isMax($ulid, $format);
    }
}
