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

use Ramsey\Identifier\NodeBasedUuid;
use Ramsey\Identifier\TimeBasedUuid;
use Ramsey\Identifier\Uuid\Utility\NodeBased;
use Ramsey\Identifier\Uuid\Utility\Standard;
use Ramsey\Identifier\Uuid\Utility\TimeBased;

/**
 * Gregorian time, or version 1, UUIDs include timestamp, clock sequence, and node values that are combined into a
 * 128-bit unsigned integer.
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562#section-5.1 RFC 9562, section 5.1. UUID Version 1.
 */
final readonly class UuidV1 implements NodeBasedUuid, TimeBasedUuid
{
    use Standard;
    use NodeBased;
    use TimeBased;

    public function getVersion(): Version
    {
        return Version::GregorianTime;
    }
}
