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
final class UuidV1 implements NodeBasedUuidInterface
{
    use NodeBasedUuid;

    public function getVersion(): Version
    {
        return Version::GregorianTime;
    }

    protected function getTimestamp(): string
    {
        $uuid = $this->getFormat(Format::String, $this->uuid);

        return sprintf(
            '%03x%04s%08s',
            hexdec(substr($uuid, 14, 4)) & 0x0fff,
            substr($uuid, 9, 4),
            substr($uuid, 0, 8),
        );
    }
}
