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
 * Reordered time, or version 6, UUIDs include timestamp, clock sequence, and
 * node values that are combined into a 128-bit unsigned integer
 *
 * @link https://www.ietf.org/archive/id/draft-ietf-uuidrev-rfc4122bis-00.html#section-5.6 rfc4122bis: UUID Version 6
 */
final readonly class UuidV6 implements JsonSerializable, NodeBasedUuid, TimeBasedUuid
{
    use Standard;
    use NodeBased;
    use TimeBased;

    public function getVersion(): Version
    {
        return Version::ReorderedGregorianTime;
    }
}
