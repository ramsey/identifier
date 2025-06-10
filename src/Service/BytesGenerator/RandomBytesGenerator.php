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

namespace Ramsey\Identifier\Service\BytesGenerator;

use DateTimeInterface;

use function random_bytes;

/**
 * A generator that uses PHP's built-in `random_bytes()` function to generate cryptographically secure random bytes.
 *
 * @link https://www.php.net/random_bytes random_bytes().
 */
final class RandomBytesGenerator implements BytesGenerator
{
    public function bytes(int $length = 16, ?DateTimeInterface $dateTime = null): string
    {
        return random_bytes($length);
    }
}
