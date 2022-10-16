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

use JsonSerializable;
use Ramsey\Identifier\Uuid\Utility\Format;
use Ramsey\Identifier\Uuid\Utility\StandardUuid;
use Ramsey\Identifier\UuidIdentifier;

use function hexdec;
use function sprintf;
use function substr;

/**
 * Version 8, Custom UUIDs provide an RFC 4122 compatible format for
 * experimental or vendor-specific uses
 *
 * The only requirement for version 8 UUIDs is that the version and variant bits
 * must be set. Otherwise, implementations are free to set the other bits
 * according to their needs. As a result, the uniqueness of version 8 UUIDs is
 * implementation-specific and should not be assumed.
 *
 * @link https://datatracker.ietf.org/doc/html/draft-peabody-dispatch-new-uuid-format-04#section-5.3 UUID Version 8
 *
 * @psalm-immutable
 */
final class UuidV8 implements JsonSerializable, UuidIdentifier
{
    use StandardUuid;

    /**
     * Returns the first 48 bits of the layout
     */
    public function getCustomFieldA(): string
    {
        $uuid = $this->getFormat(Format::FORMAT_STRING);

        return substr($uuid, 0, 8) . substr($uuid, 9, 4);
    }

    /**
     * Returns the 12 bits of the layout following the version bits
     */
    public function getCustomFieldB(): string
    {
        return substr($this->getFormat(Format::FORMAT_STRING), 15, 3);
    }

    /**
     * Returns the 62 bits of the layout following the variant bits
     */
    public function getCustomFieldC(): string
    {
        $uuid = $this->getFormat(Format::FORMAT_STRING);
        $clockSeqLow = hexdec(substr($uuid, 19, 4)) & 0x3fff;

        return sprintf('%04x%012s', $clockSeqLow, substr($uuid, -12));
    }

    public function getVersion(): Version
    {
        return Version::Custom;
    }
}
