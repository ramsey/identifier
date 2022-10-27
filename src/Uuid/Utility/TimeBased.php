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

namespace Ramsey\Identifier\Uuid\Utility;

use DateTimeImmutable;

/**
 * This internal trait provides functionality common to time-based UUIDs
 *
 * @internal
 *
 * @psalm-immutable
 */
trait TimeBased
{
    use Standard;

    public function getDateTime(): DateTimeImmutable
    {
        /** @psalm-suppress ImpureMethodCall */
        return (new Time())->getDateTimeForUuid($this);
    }
}
