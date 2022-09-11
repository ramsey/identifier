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

use DateTimeImmutable;
use Exception;
use Identifier\TimeBasedUuidInterface;
use Identifier\Uuid\Version;

use function explode;
use function hexdec;
use function number_format;
use function sprintf;

/**
 * @psalm-immutable
 */
final class UuidV7 implements TimeBasedUuidInterface
{
    use TimeBasedUuid;

    /**
     * @throws Exception When unable to create a DateTimeImmutable instance.
     */
    public function getDateTime(): DateTimeImmutable
    {
        $unixTimestamp = number_format(hexdec($this->getTimestamp()) / 1000, 6, '.', '');

        return new DateTimeImmutable('@' . $unixTimestamp);
    }

    public function getVersion(): Version
    {
        return Version::UnixTime;
    }

    protected function getValidationPattern(): string
    {
        return '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/Di';
    }

    /**
     * Returns a 48-bit timestamp as a hexadecimal string representing the Unix
     * Epoch in milliseconds
     */
    protected function getTimestamp(): string
    {
        $fields = explode('-', $this->uuid);

        return sprintf('%08s%04s', $fields[0], $fields[1]);
    }
}
