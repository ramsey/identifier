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

use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Utility\Standard;

/**
 * Version 3 UUIDs are named-based, using a combination of a namespace and name
 * that are hashed into a 128-bit unsigned integer using the MD5 hashing
 * algorithm
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562#section-5.3 RFC 9562, section 5.3. UUID Version 3
 */
final readonly class UuidV3 implements Uuid
{
    use Standard;

    public function getVersion(): Version
    {
        return Version::NameMd5;
    }
}
