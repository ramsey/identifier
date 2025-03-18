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

namespace Ramsey\Identifier\Service\Sequence;

use function random_int;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * Uses PHP's `random_int()` function to always generate a random sequence value.
 *
 * WARNING: Sequence values generated using this method are not sequential.
 *
 * @link https://www.php.net/random_int random_int()
 */
final class RandomSequence implements Sequence
{
    public function next(?string $state = null): int
    {
        return random_int(PHP_INT_MIN, PHP_INT_MAX);
    }
}
