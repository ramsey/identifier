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
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Utility\Standard;

/**
 * Version 8, Custom UUIDs provide an RFC 4122 compatible format for
 * experimental or vendor-specific uses
 *
 * The only requirement for version 8 UUIDs is that the version and variant bits
 * must be set. Otherwise, implementations are free to set the other bits
 * according to their needs. As a result, the uniqueness of version 8 UUIDs is
 * implementation-specific and should not be assumed.
 *
 * @link https://www.ietf.org/archive/id/draft-ietf-uuidrev-rfc4122bis-00.html#section-5.8 rfc4122bis: UUID Version 8
 */
final readonly class UuidV8 implements JsonSerializable, Uuid
{
    use Standard;

    public function getVersion(): Version
    {
        return Version::Custom;
    }
}
