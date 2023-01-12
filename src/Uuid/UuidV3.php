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
 * Version 3 UUIDs are named-based, using a combination of a namespace and name
 * that are hashed into a 128-bit unsigned integer using the MD5 hashing
 * algorithm
 *
 * @link https://www.rfc-editor.org/rfc/rfc4122.html#section-4.1 RFC 4122: Format
 * @link https://www.ietf.org/archive/id/draft-ietf-uuidrev-rfc4122bis-00.html#section-5.3 rfc4122bis: UUID Version 3
 *
 * @psalm-immutable
 */
final class UuidV3 implements JsonSerializable, Uuid
{
    use Standard;

    public function getVersion(): Version
    {
        return Version::HashMd5;
    }
}
