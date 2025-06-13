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

namespace Ramsey\Identifier\Uuid;

use Ramsey\Identifier\Exception\BadMethodCall;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Internal\Format;
use Ramsey\Identifier\Uuid\Internal\Standard;

use function sprintf;
use function strlen;

/**
 * The Max UUID is a special form of UUID that has all 128 bits set to one (1).
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562#section-5.10 RFC 9562, section 5.10: Max UUID.
 */
final readonly class MaxUuid implements Uuid
{
    use Standard;

    private const MAX = "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";

    /**
     * @param non-empty-string $uuid A representation of the UUID as a string with dashes, hexadecimal, or byte string.
     *
     * @throws InvalidArgument
     */
    public function __construct(private string $uuid = self::MAX)
    {
        $this->format = Format::tryFrom(strlen($this->uuid));

        if (!$this->isValid($this->uuid, $this->format)) {
            throw new InvalidArgument(sprintf('Invalid Max UUID: "%s"', $this->uuid));
        }
    }

    /**
     * {@inheritDoc}
     *
     * According to RFC 9562 sections {@link https://www.rfc-editor.org/rfc/rfc9562#section-4.1 4.1} and
     * {@link https://www.rfc-editor.org/rfc/rfc9562#section-5.10 5.10}, the Max UUID falls within the range
     * of the future variant.
     */
    public function getVariant(): Variant
    {
        return Variant::Future;
    }

    /**
     * @throws BadMethodCall
     */
    public function getVersion(): never
    {
        throw new BadMethodCall('Max UUIDs do not have a version field');
    }

    private function isValid(string $uuid, ?Format $format): bool
    {
        return $this->isMax($uuid, $format);
    }
}
