<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is open source software: you can distribute it and/or modify it under the terms of the MIT License
 * (the "License"). You may not use this file except in compliance with the License.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Uuid;

use Ramsey\Identifier\TimeBasedUuid;
use Ramsey\Identifier\Uuid\Utility\Standard;
use Ramsey\Identifier\Uuid\Utility\TimeBased;

/**
 * Unix Epoch time, or version 7, UUIDs include a timestamp in milliseconds since the Unix Epoch.
 *
 * Version 7 UUIDs are designed to be monotonically increasing and sortable.
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562#section-5.7 RFC 9562, section 5.7. UUID Version 7.
 */
final readonly class UuidV7 implements TimeBasedUuid
{
    use Standard;
    use TimeBased;

    public function getVersion(): Version
    {
        return Version::UnixTime;
    }
}
