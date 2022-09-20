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

use DateTimeImmutable;
use Exception;
use Identifier\Uuid\TimeBasedUuidInterface;
use Identifier\Uuid\Version;

use function hexdec;
use function number_format;
use function sprintf;
use function substr;

/**
 * Gregorian time, or version 1, UUIDs include timestamp, clock sequence, and node
 * values that are combined into a 128-bit unsigned integer
 *
 * @link https://datatracker.ietf.org/doc/html/draft-peabody-dispatch-new-uuid-format-04#section-5.2 UUID Version 7
 *
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

    /**
     * Returns a 48-bit timestamp as a hexadecimal string representing the Unix
     * Epoch in milliseconds
     */
    protected function getTimestamp(): string
    {
        $uuid = $this->getFormat(Format::String, $this->uuid);

        return sprintf('%08s%04s', substr($uuid, 0, 8), substr($uuid, 9, 4));
    }
}
