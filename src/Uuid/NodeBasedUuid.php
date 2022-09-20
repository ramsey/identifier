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

use function substr;

/**
 * This internal trait provides functionality common to node-based UUIDs
 *
 * @internal
 *
 * @psalm-immutable
 */
trait NodeBasedUuid
{
    use TimeBasedUuid;

    /**
     * @return non-empty-string
     */
    public function getNode(): string
    {
        /** @var non-empty-string */
        return substr($this->getFormat(Format::Hexadecimal, $this->uuid), -12);
    }
}
