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
 * The Nil ULID is a special form of ULID that has all 128 bits set to zero (`0`).
 *
 * @link https://github.com/ulid/spec ULID specification.
 */
final readonly class NilUlid implements UlidInterface
{
    use Standard;

    private const NIL = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";

    /**
     * @param non-empty-string $ulid A representation of a ULID in either Crockford base-32 or byte form.
     *
     * @throws InvalidArgument
     */
    public function __construct(private string $ulid = self::NIL)
    {
        $this->format = Format::tryFrom(strlen($ulid));

        if (!$this->isValid($this->ulid, $this->format)) {
            throw new InvalidArgument(sprintf('Invalid Nil ULID: "%s"', $this->ulid));
        }
    }

    /**
     * @phpstan-assert-if-true non-empty-string $ulid
     */
    private function isValid(string $ulid, ?Format $format): bool
    {
        return $this->isNil($ulid, $format);
    }
}
