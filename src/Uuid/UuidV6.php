<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is open source software: you can distribute
 * it and/or modify it under the terms of the MIT License
 * (the "License"). You may not use this file except in
 * compliance with the License.
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
 * implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Uuid;

use Identifier\Uuid\NodeBasedUuidInterface;
use Identifier\Uuid\Version;

use function hexdec;
use function sprintf;
use function substr;

/**
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
        $uuid = $this->getFormat(Format::String, $this->uuid);

        return sprintf(
            '%08s%04s%03x',
            substr($uuid, 0, 8),
            substr($uuid, 9, 4),
            hexdec(substr($uuid, 14, 4)) & 0x0fff,
        );
    }
}
