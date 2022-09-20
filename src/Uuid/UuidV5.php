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

use Identifier\Uuid\UuidInterface;
use Identifier\Uuid\Version;

/**
 * Version 5 UUIDs are named-based, using a combination of a namespace and name
 * that are hashed into a 128-bit unsigned integer using the SHA-1 hashing
 * algorithm
 *
 * @link https://datatracker.ietf.org/doc/html/rfc4122#section-4.1 Format
 *
 * @psalm-immutable
 */
final class UuidV5 implements UuidInterface
{
    use StandardUuid;

    public function getVersion(): Version
    {
        return Version::HashSha1;
    }
}
