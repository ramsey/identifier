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

namespace Ramsey\Identifier\Ulid;

use Ramsey\Identifier\Ulid as UlidInterface;
use Ramsey\Identifier\Ulid\Utility\Standard;

/**
 * A universally unique lexicographically sortable identifier (ULID).
 *
 * A ULID consists of a 48-bit integer representing the milliseconds since the Unix Epoch and 80 bits of randomness from
 * a cryptographically secure source. Together, they form a 128-bit unsigned integer that is binary-compatible with
 * UUIDs. When encoded in string form, ULIDs use Crockford base-32 encoding, allowing 5 bits per character. As a result,
 * string ULIDs are 26 characters instead of the 36 characters that make up a UUID.
 *
 * @link https://github.com/ulid/spec ULID specification.
 * @link https://www.crockford.com/base32.html Crockford base-32 specification.
 */
final readonly class Ulid implements UlidInterface
{
    use Standard;
}
