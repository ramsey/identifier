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
 * Random, or version 4, UUIDs are randomly or pseudo-randomly generated 128-bit
 * integers
 *
 * @link https://datatracker.ietf.org/doc/html/rfc4122#section-4.1 Format
 *
 * @psalm-immutable
 */
final class UuidV4 implements JsonSerializable, Uuid
{
    use Standard;

    public function getVersion(): Version
    {
        return Version::Random;
    }
}
