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

use Identifier\TimeBasedUuidInterface;
use Identifier\Uuid\Version;

use function explode;
use function hexdec;
use function sprintf;

/**
 * @psalm-immutable
 */
final class UuidV2 implements TimeBasedUuidInterface
{
    use TimeBasedUuid;

    public function getVersion(): Version
    {
        return Version::DceSecurity;
    }

    protected function getValidationPattern(): string
    {
        return '/^[0-9a-f]{8}-[0-9a-f]{4}-2[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/Di';
    }

    /**
     * Returns the full 60-bit timestamp as a hexadecimal string, without the version
     *
     * For version 2 UUIDs, the time_low field is the local identifier and
     * should not be returned as part of the time. For this reason, we set the
     * bottom 32 bits of the timestamp to 0's. As a result, there is some loss
     * of fidelity of the timestamp, for version 2 UUIDs. The timestamp can be
     * off by a range of 0 to 429.4967295 seconds (or 7 minutes, 9 seconds, and
     * 496730 microseconds).
     */
    protected function getTimestamp(): string
    {
        $fields = explode('-', $this->uuid);

        return sprintf(
            '%03x%04s%08s',
            hexdec($fields[2]) & 0x0fff,
            $fields[1],
            '',
        );
    }
}
