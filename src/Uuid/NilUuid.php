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
 * The Nil UUID is a special form of UUID that has all 128 bits set to zero (0).
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562#section-5.9 RFC 9562, section 5.9. Nil UUID.
 */
final readonly class NilUuid implements Uuid
{
    use Standard;

    private const NIL = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";

    /**
     * @param non-empty-string $uuid A representation of the UUID as a string with dashes, hexadecimal, or byte string.
     *
     * @throws InvalidArgument
     */
    public function __construct(private string $uuid = self::NIL)
    {
        $this->format = Format::tryFrom(strlen($this->uuid));

        if (!$this->isValid($this->uuid, $this->format)) {
            throw new InvalidArgument(sprintf('Invalid Nil UUID: "%s"', $this->uuid));
        }
    }

    /**
     * {@inheritDoc}
     *
     * According to RFC 9562 sections {@link https://www.rfc-editor.org/rfc/rfc9562#section-4.1 4.1} and
     * {@link https://www.rfc-editor.org/rfc/rfc9562#section-5.9 5.9}, the Nil UUID falls within the range of the Apollo
     * NCS variant.
     */
    public function getVariant(): Variant
    {
        return Variant::Ncs;
    }

    /**
     * @throws BadMethodCall
     */
    public function getVersion(): never
    {
        throw new BadMethodCall('Nil UUIDs do not have a version field');
    }

    private function isValid(string $uuid, ?Format $format): bool
    {
        return $this->isNil($uuid, $format);
    }
}
