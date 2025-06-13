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

use JsonSerializable;
use Ramsey\Identifier\Exception\BadMethodCall;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Internal\Format;
use Ramsey\Identifier\Uuid\Internal\Standard;

use function assert;
use function sprintf;
use function strlen;

/**
 * Nonstandard UUIDs look like UUIDs, but they do not have the variant and version bits set according to RFC 9562.
 *
 * It is possible a nonstandard UUID was generated according to RFC 9562 but had its bytes rearranged for reasons such
 * as sortability. For example, before the introduction of UUID versions 6 and 7, it was popular to rearrange the bytes
 * of UUIDs for sorting purposes. One such arrangement was the "ordered time" UUID, which reordered the timestamp bytes
 * of a version 1 UUID. Another was the timestamp-first combined (COMB) UUID, which embedded a timestamp at the
 * beginning of a version 4 UUID.
 *
 * Without knowing which rearrangement algorithm was used, it is impossible to determine the UUID's original layout, so
 * we treat it as a "nonstandard" UUID.
 */
final readonly class NonstandardUuid implements JsonSerializable, Uuid
{
    use Standard;

    private ?Variant $variant;

    /**
     * @param non-empty-string $uuid A representation of the UUID as a string with dashes, hexadecimal, or byte string.
     *
     * @throws InvalidArgument
     */
    public function __construct(private string $uuid)
    {
        $this->format = Format::tryFrom(strlen($this->uuid));
        $this->variant = $this->getVariantFromUuid($this->uuid, $this->format);

        if (!$this->isValid($this->uuid, $this->format)) {
            throw new InvalidArgument(sprintf('Invalid nonstandard UUID: "%s"', $this->uuid));
        }
    }

    public function getVariant(): Variant
    {
        assert($this->variant !== null);

        return $this->variant;
    }

    /**
     * @throws BadMethodCall
     */
    public function getVersion(): never
    {
        throw new BadMethodCall('Nonstandard UUIDs do not have a version field');
    }

    private function isValid(string $uuid, ?Format $format): bool
    {
        if (!$this->hasValidFormat($uuid, $format)) {
            return false;
        }

        if ($this->isMax($uuid, $format) || $this->isNil($uuid, $format)) {
            return false;
        }

        if ($this->variant !== Variant::Rfc && $this->variant !== Variant::Microsoft) {
            return true;
        }

        $version = $this->getVersionFromUuid($uuid, $format, $this->variant === Variant::Microsoft);

        // Version 2 UUIDs that do not have a proper domain are nonstandard.
        if ($version === 2 && $this->getLocalDomainFromUuid($uuid, $format) === null) {
            return true;
        }

        return $version < 1 || $version > 8;
    }
}
