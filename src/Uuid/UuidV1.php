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
use Ramsey\Identifier\NodeBasedUuid;
use Ramsey\Identifier\TimeBasedUuid;
use Ramsey\Identifier\Uuid\Utility\NodeBased;
use Ramsey\Identifier\Uuid\Utility\Standard;
use Ramsey\Identifier\Uuid\Utility\TimeBased;

/**
 * Gregorian time, or version 1, UUIDs include timestamp, clock sequence, and node
 * values that are combined into a 128-bit unsigned integer
 *
 * @link https://datatracker.ietf.org/doc/html/rfc4122#section-4.1 Format
 *
 * @psalm-immutable
 */
final class UuidV1 implements JsonSerializable, NodeBasedUuid, TimeBasedUuid
{
    use Standard;
    use NodeBased;
    use TimeBased;

    public function getVersion(): Version
    {
        return Version::GregorianTime;
    }
}
