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

namespace Ramsey\Identifier\Uuid;

use Override;
use Ramsey\Identifier\Exception\BadMethodCall;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Internal\Format;
use Ramsey\Identifier\Uuid\Internal\Standard;

use function sprintf;
use function strlen;

/**
 * Nonstandard UUIDs look like UUIDs, but they do not have the variant and
 * version bits set according to RFC 9562
 *
 * It is possible a nonstandard UUID was generated according to RFC 9562 but had
 * its bytes rearranged for reasons such as sortability. For example, before the
 * introduction of UUID versions 6 and 7, it was popular to rearrange the bytes
 * of UUIDs for sorting purposes. One such arrangement was the "ordered time"
 * UUID, which reordered the timestamp bytes of a version 1 UUID. Another was
 * the timestamp-first combined (COMB) UUID, which embedded a timestamp at the
 * beginning of a version 4 UUID.
 *
 * Without knowing which rearrangement algorithm was used, it is impossible to
 * determine the UUID's original layout, so we treat it as a "nonstandard" UUID.
 */
final readonly class NonstandardUuid implements Uuid
{
    use Standard;

    private Variant $variant;

    /**
     * @param non-empty-string $uuid A representation of the UUID as a string
     *     with dashes, hexadecimal, or byte string
     *
     * @throws InvalidArgument
     */
    public function __construct(private string $uuid)
    {
        $format = Format::tryFrom(strlen($this->uuid));
        $variant = null;

        if ($format !== null && $this->hasValidFormat($this->uuid, $format)) {
            $variant = $this->getVariantFromUuid($this->uuid, $format);
        }

        if ($format === null || $variant === null || !$this->isNonstandard($this->uuid, $format, $variant)) {
            throw new InvalidArgument(sprintf('Invalid nonstandard UUID: "%s"', $this->uuid));
        }

        $this->format = $format;
        $this->variant = $variant;
    }

    #[Override]
    public function getVariant(): Variant
    {
        return $this->variant;
    }

    /**
     * @throws BadMethodCall
     */
    #[Override]
    public function getVersion(): never
    {
        throw new BadMethodCall('Nonstandard UUIDs do not have a version field');
    }

    /**
     * Returns true if a UUID that has a valid format, and the given variant,
     * is a nonstandard UUID
     */
    private function isNonstandard(string $uuid, Format $format, Variant $variant): bool
    {
        if ($this->isMax($uuid, $format) || $this->isNil($uuid, $format)) {
            return false;
        }

        if ($variant !== Variant::Rfc && $variant !== Variant::Microsoft) {
            return true;
        }

        $version = $this->getVersionFromUuid($uuid, $format);

        // Version 2 UUIDs that do not have a proper domain are nonstandard.
        if ($version === 2 && $this->getLocalDomainFromUuid($uuid, $format) === null) {
            return true;
        }

        return $version < 1 || $version > 8;
    }
}
