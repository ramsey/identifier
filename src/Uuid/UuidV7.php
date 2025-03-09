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
use Ramsey\Identifier\TimeBasedUuid;
use Ramsey\Identifier\Uuid\Utility\Standard;
use Ramsey\Identifier\Uuid\Utility\TimeBased;

/**
 * Unix Epoch time, or version 7, UUIDs include a timestamp in milliseconds
 * since the Unix Epoch
 *
 * Version 7 UUIDs are designed to be monotonically increasing and sortable.
 *
 * @link https://www.ietf.org/archive/id/draft-ietf-uuidrev-rfc4122bis-00.html#section-5.7 rfc4122bis: UUID Version 7
 */
final readonly class UuidV7 implements JsonSerializable, TimeBasedUuid
{
    use Standard;
    use TimeBased;

    public function getVersion(): Version
    {
        return Version::UnixTime;
    }
}
