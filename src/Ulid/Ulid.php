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

namespace Ramsey\Identifier\Ulid;

use Identifier\BinaryIdentifier;
use Identifier\DateTimeIdentifier;
use Identifier\IntegerIdentifier;
use JsonSerializable;
use Ramsey\Identifier\Ulid\Utility\StandardUlid;

/**
 * @psalm-immutable
 */
final class Ulid implements BinaryIdentifier, DateTimeIdentifier, IntegerIdentifier, JsonSerializable
{
    use StandardUlid;
}
