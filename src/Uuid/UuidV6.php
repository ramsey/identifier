<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is open source software: you can distribute
 * it and/or modify it under the terms of the MIT License
 * (the "License"). You may not use this file except in
 * compliance with the License.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Uuid;

use Identifier\Uuid\NodeBasedUuidInterface;
use Identifier\Uuid\Version;
use Ramsey\Identifier\Uuid\Utility\Format;
use Ramsey\Identifier\Uuid\Utility\NodeBasedUuid;

use function hexdec;
use function sprintf;
use function substr;

/**
 * Reordered time, or version 6, UUIDs include timestamp, clock sequence, and
 * node values that are combined into a 128-bit unsigned integer
 *
 * @link https://datatracker.ietf.org/doc/html/draft-peabody-dispatch-new-uuid-format-04#section-5.1 UUID Version 6
 *
 * @psalm-immutable
 */
final class UuidV6 implements NodeBasedUuidInterface
{
    use NodeBasedUuid;

    public function getVersion(): Version
    {
        return Version::ReorderedGregorianTime;
    }

    /**
     * Returns the full 60-bit timestamp as a hexadecimal string, without the version
     *
     * For version 6 UUIDs, the timestamp order is reversed from the typical RFC
     * 4122 order (the time bits are in the correct bit order, so that it is
     * monotonically increasing). In returning the timestamp value, we put the
     * bits in the order: time_low + time_mid + time_hi.
     */
    protected function getTimestamp(): string
    {
        $uuid = $this->getFormat(Format::FORMAT_STRING);

        return sprintf(
            '%08s%04s%03x',
            substr($uuid, 0, 8),
            substr($uuid, 9, 4),
            hexdec(substr($uuid, 14, 4)) & 0x0fff,
        );
    }
}
